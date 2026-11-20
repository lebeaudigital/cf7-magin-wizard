<?php
/**
 * Conditions d'affichage des champs Contact Form 7.
 *
 * Une règle se lit comme une phrase :
 *   si [source] [opérateur] [valeur] alors [afficher|masquer] [cible]
 *
 * Plusieurs règles « afficher » sur le même champ : une seule suffit.
 * Une règle « masquer » qui correspond l'emporte.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CF7MW_Conditions {

    public const OPERATORS = [
        'equals'       => 'est',
        'not_equals'   => "n'est pas",
        'contains'     => 'contient',
        'not_contains' => 'ne contient pas',
        'empty'        => 'est vide',
        'not_empty'    => "n'est pas vide",
        'gt'           => 'est supérieur à',
        'lt'           => 'est inférieur à',
    ];

    public const ACTIONS = [
        'show' => 'afficher',
        'hide' => 'masquer',
    ];

    private static ?CF7MW_Conditions $instance = null;

    public static function get_instance(): CF7MW_Conditions {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('wpcf7_posted_data', [$this, 'strip_hidden_posted_data'], 20);
        add_filter('wpcf7_validate', [$this, 'clear_hidden_errors'], 99, 2);
    }

    /**
     * Champs nommés du modèle CF7, pour les listes du panneau.
     *
     * @return array<int,array{name:string,type:string,label:string,options:array<int,array{value:string,label:string}>}>
     */
    public static function parse_fields(string $content): array {
        $types = 'text|email|tel|url|number|date|textarea|select|checkbox|radio|acceptance|quiz|range|hidden';
        if (!preg_match_all('/\[(' . $types . ')\*?([^\]]*)\]/i', $content, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $fields = [];
        foreach ($matches as $match) {
            $parsed = self::parse_tag_body((string) $match[2]);
            $name   = $parsed['name'];
            if ($name === '' || isset($fields[$name])) {
                continue;
            }
            $fields[$name] = [
                'name'    => $name,
                'type'    => strtolower((string) $match[1]),
                'label'   => $parsed['label'] !== '' ? $parsed['label'] : $name,
                'options' => $parsed['options'],
            ];
        }

        return array_values($fields);
    }

    /**
     * @param array<int,mixed> $rules
     * @return array<int,array{source:string,operator:string,value:string,action:string,target:string}>
     */
    public static function sanitize_rules(array $rules): array {
        $clean = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $source   = self::field_name((string) ($rule['source'] ?? ''));
            $target   = self::field_name((string) ($rule['target'] ?? ''));
            $operator = (string) ($rule['operator'] ?? 'equals');
            $action   = (string) ($rule['action'] ?? 'show');
            if (!isset(self::OPERATORS[$operator])) {
                $operator = 'equals';
            }
            if (!isset(self::ACTIONS[$action])) {
                $action = 'show';
            }
            if ($source === '' || $target === '' || $source === $target) {
                continue;
            }
            $clean[] = [
                'source'   => $source,
                'operator' => $operator,
                'value'    => sanitize_text_field((string) ($rule['value'] ?? '')),
                'action'   => $action,
                'target'   => $target,
            ];
        }
        return $clean;
    }

    /**
     * Noms des champs qui doivent être masqués pour les valeurs envoyées.
     *
     * @param array<int,array<string,string>> $rules
     * @param array<string,mixed> $posted
     * @return string[]
     */
    public static function hidden_targets(array $rules, array $posted): array {
        $grouped = [];
        foreach ($rules as $rule) {
            $target = (string) ($rule['target'] ?? '');
            if ($target === '') {
                continue;
            }
            $grouped[$target][] = $rule;
        }

        $hidden = [];
        foreach ($grouped as $target => $group) {
            $show_rules = array_values(array_filter($group, static fn (array $rule): bool => ($rule['action'] ?? '') === 'show'));
            $hide_hit   = false;
            foreach ($group as $rule) {
                if (($rule['action'] ?? '') === 'hide' && self::matches($rule, $posted)) {
                    $hide_hit = true;
                    break;
                }
            }
            if ($hide_hit) {
                $hidden[] = $target;
                continue;
            }
            if ($show_rules === []) {
                continue;
            }
            $show_hit = false;
            foreach ($show_rules as $rule) {
                if (self::matches($rule, $posted)) {
                    $show_hit = true;
                    break;
                }
            }
            if (!$show_hit) {
                $hidden[] = $target;
            }
        }

        return $hidden;
    }

    /**
     * @param array<string,string> $rule
     * @param array<string,mixed> $posted
     */
    public static function matches(array $rule, array $posted): bool {
        $source = (string) ($rule['source'] ?? '');
        $raw    = $posted[$source] ?? '';
        if (is_array($raw)) {
            $values = array_map(static fn ($item): string => trim((string) $item), $raw);
            $value  = implode(' ', $values);
        } else {
            $value  = trim((string) $raw);
            $values = [$value];
        }
        $expected = trim((string) ($rule['value'] ?? ''));
        $operator = (string) ($rule['operator'] ?? 'equals');

        $equals = static function () use ($values, $value, $expected): bool {
            if ($expected === '') {
                return $value === '';
            }
            return in_array($expected, $values, true);
        };

        return match ($operator) {
            'equals'       => $equals(),
            'not_equals'   => !$equals(),
            'contains'     => $expected !== '' && str_contains(self::lower($value), self::lower($expected)),
            'not_contains' => $expected === '' || !str_contains(self::lower($value), self::lower($expected)),
            'empty'        => $value === '',
            'not_empty'    => $value !== '',
            'gt'           => is_numeric($value) && is_numeric($expected) && (float) $value > (float) $expected,
            'lt'           => is_numeric($value) && is_numeric($expected) && (float) $value < (float) $expected,
            default        => false,
        };
    }

    /**
     * Retire les champs masqués des données envoyées (e-mail, enregistrement).
     *
     * @param array<string,mixed> $posted
     * @return array<string,mixed>
     */
    public function strip_hidden_posted_data(array $posted): array {
        foreach ($this->hidden_names_for_current_form($posted) as $name) {
            unset($posted[$name]);
        }
        return $posted;
    }

    /**
     * Un champ obligatoire masqué ne doit pas bloquer l'envoi.
     *
     * @param mixed $result
     * @param mixed $tags
     * @return mixed
     */
    public function clear_hidden_errors($result, $tags = null) {
        unset($tags);
        if (!$result instanceof WPCF7_Validation) {
            return $result;
        }

        $form = WPCF7_ContactForm::get_current();
        if (!$form) {
            return $result;
        }
        $posted = [];
        $submission = WPCF7_Submission::get_instance();
        if ($submission) {
            $posted = $submission->get_posted_data();
        }
        $hidden = $this->hidden_names_for_current_form(is_array($posted) ? $posted : []);
        if ($hidden === []) {
            return $result;
        }

        $property = new ReflectionProperty(WPCF7_Validation::class, 'invalid_fields');
        $invalid  = $property->getValue($result);
        if (!is_array($invalid)) {
            return $result;
        }
        foreach ($hidden as $name) {
            unset($invalid[$name]);
        }
        $property->setValue($result, $invalid);
        return $result;
    }

    /**
     * @param array<string,mixed> $posted
     * @return string[]
     */
    private function hidden_names_for_current_form(array $posted): array {
        $form = WPCF7_ContactForm::get_current();
        if (!$form) {
            return [];
        }
        $config = CF7MW_Config::get((int) $form->id());
        $rules  = is_array($config['rules'] ?? null) ? $config['rules'] : [];
        if ($rules === []) {
            return [];
        }
        return self::hidden_targets($rules, $posted);
    }

    /**
     * @return array{name:string,label:string,options:array<int,array{value:string,label:string}>}
     */
    private static function parse_tag_body(string $body): array {
        $name    = '';
        $label   = '';
        $options = [];
        $expect_placeholder = false;

        foreach (self::tokenize($body) as $token) {
            if ($expect_placeholder) {
                $label = trim($token, "\"'");
                $expect_placeholder = false;
                continue;
            }
            if ($token === 'placeholder') {
                $expect_placeholder = true;
                continue;
            }
            if (str_starts_with($token, 'placeholder:')) {
                $label = substr($token, strlen('placeholder:'));
                continue;
            }
            if ($name === '' && !str_contains($token, ':')) {
                $name = trim($token, "\"'");
                continue;
            }
            if (str_starts_with($token, '"') || str_starts_with($token, "'")) {
                $raw = trim($token, "\"'");
                if (str_contains($raw, '|')) {
                    [$option_label, $option_value] = explode('|', $raw, 2);
                } else {
                    $option_label = $raw;
                    $option_value = $raw;
                }
                $option_label = trim($option_label);
                $option_value = trim($option_value);
                if ($option_value !== '') {
                    $options[] = [
                        'value' => $option_value,
                        'label' => $option_label !== '' ? $option_label : $option_value,
                    ];
                }
            }
        }

        return [
            'name'    => self::field_name($name),
            'label'   => $label,
            'options' => $options,
        ];
    }

    /**
     * @return string[]
     */
    private static function tokenize(string $body): array {
        $tokens = [];
        $length = strlen($body);
        $buffer = '';
        $quote  = '';
        for ($i = 0; $i < $length; $i++) {
            $char = $body[$i];
            if ($quote !== '') {
                $buffer .= $char;
                if ($char === $quote) {
                    $tokens[] = $buffer;
                    $buffer = '';
                    $quote = '';
                }
                continue;
            }
            if ($char === '"' || $char === "'") {
                if ($buffer !== '') {
                    $tokens[] = $buffer;
                    $buffer = '';
                }
                $quote = $char;
                $buffer = $char;
                continue;
            }
            if (ctype_space($char)) {
                if ($buffer !== '') {
                    $tokens[] = $buffer;
                    $buffer = '';
                }
                continue;
            }
            $buffer .= $char;
        }
        if ($buffer !== '') {
            $tokens[] = $buffer;
        }
        return $tokens;
    }

    private static function field_name(string $name): string {
        $name = trim($name);
        if (!preg_match('/^[A-Za-z][-A-Za-z0-9_:.]*$/', $name)) {
            return '';
        }
        return $name;
    }

    private static function lower(string $value): string {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }
}
