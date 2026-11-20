<?php
/**
 * Onglet « Conditions » dans l'éditeur Contact Form 7.
 *
 * Variables disponibles :
 *   $form   WPCF7_ContactForm
 *   $config array Configuration du formulaire
 */

if (!defined('ABSPATH')) {
    exit;
}

$form_content     = (string) $form->prop('form');
$condition_fields = CF7MW_Conditions::parse_fields($form_content);
$condition_rules  = is_array($config['rules'] ?? null) ? $config['rules'] : [];
?>
<div class="cf7mw-admin">
    <div class="cf7mw-card" data-cf7mw-conditions-root>
        <h3 class="cf7mw-card__title"><?php esc_html_e('Conditions', 'cf7-magic-wizard'); ?></h3>
        <p class="cf7mw-card__desc">
            <?php esc_html_e('Affichez ou masquez un champ selon la réponse donnée à un autre. Cet onglet est indépendant du wizard : les conditions s’appliquent même si le formulaire reste sur une seule page. Un champ masqué n’est pas obligatoire et n’est pas envoyé.', 'cf7-magic-wizard'); ?>
        </p>
        <div class="cf7mw-rules" data-cf7mw-rules>
            <?php foreach ($condition_rules as $index => $rule) :
                $operator    = (string) ($rule['operator'] ?? 'equals');
                $action      = (string) ($rule['action'] ?? 'show');
                $needs_value = !in_array($operator, ['empty', 'not_empty'], true);
                $targets = [];
                if (!empty($rule['targets']) && is_array($rule['targets'])) {
                    $targets = array_values($rule['targets']);
                } elseif (!empty($rule['target'])) {
                    $targets = [$rule['target']];
                }
                if ($targets === []) {
                    $targets = [''];
                }
                ?>
                <div class="cf7mw-rule<?php echo $needs_value ? '' : ' is-valueless'; ?>" data-cf7mw-rule>
                    <span class="cf7mw-rule__word"><?php esc_html_e('Si', 'cf7-magic-wizard'); ?></span>
                    <select name="cf7mw[rules][<?php echo (int) $index; ?>][source]" data-part="source">
                        <option value=""><?php esc_html_e('Choisir un champ', 'cf7-magic-wizard'); ?></option>
                        <?php foreach ($condition_fields as $field) : ?>
                            <option value="<?php echo esc_attr($field['name']); ?>" <?php selected($rule['source'] ?? '', $field['name']); ?>>
                                <?php echo esc_html($field['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cf7mw[rules][<?php echo (int) $index; ?>][operator]" data-part="operator">
                        <?php foreach (CF7MW_Conditions::OPERATORS as $op_value => $op_label) : ?>
                            <option value="<?php echo esc_attr($op_value); ?>" <?php selected($operator, $op_value); ?>><?php echo esc_html($op_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="cf7mw-rule__value" data-part="value-wrap">
                        <input type="text" name="cf7mw[rules][<?php echo (int) $index; ?>][value]" data-part="value" value="<?php echo esc_attr((string) ($rule['value'] ?? '')); ?>" placeholder="<?php esc_attr_e('Valeur', 'cf7-magic-wizard'); ?>">
                    </span>
                    <span class="cf7mw-rule__word"><?php esc_html_e('alors', 'cf7-magic-wizard'); ?></span>
                    <select name="cf7mw[rules][<?php echo (int) $index; ?>][action]" data-part="action">
                        <?php foreach (CF7MW_Conditions::ACTIONS as $action_value => $action_label) : ?>
                            <option value="<?php echo esc_attr($action_value); ?>" <?php selected($action, $action_value); ?>><?php echo esc_html($action_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="cf7mw-rule__targets" data-part="targets">
                        <?php foreach ($targets as $target_name) : ?>
                            <span class="cf7mw-rule__target">
                                <select name="cf7mw[rules][<?php echo (int) $index; ?>][targets][]" data-part="target">
                                    <option value=""><?php esc_html_e('Choisir un champ', 'cf7-magic-wizard'); ?></option>
                                    <?php foreach ($condition_fields as $field) : ?>
                                        <option value="<?php echo esc_attr($field['name']); ?>" <?php selected((string) $target_name, $field['name']); ?>>
                                            <?php echo esc_html($field['label']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="cf7mw-rule__target-remove" data-cf7mw-remove-target aria-label="<?php esc_attr_e('Retirer ce champ', 'cf7-magic-wizard'); ?>" <?php echo count($targets) < 2 ? 'hidden' : ''; ?>>×</button>
                            </span>
                        <?php endforeach; ?>
                        <button type="button" class="cf7mw-rule__add-target" data-cf7mw-add-target aria-label="<?php esc_attr_e('Ajouter un champ', 'cf7-magic-wizard'); ?>">+</button>
                    </span>
                    <button type="button" class="cf7mw-rule__remove" data-cf7mw-remove-rule aria-label="<?php esc_attr_e('Supprimer cette condition', 'cf7-magic-wizard'); ?>">×</button>
                    <p class="cf7mw-rule__summary" data-part="summary"></p>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button cf7mw-add-rule" data-cf7mw-add-rule>
            <?php esc_html_e('+ Ajouter une condition', 'cf7-magic-wizard'); ?>
        </button>
        <p class="cf7mw-help">
            <?php esc_html_e('Le bouton + ajoute un autre champ à la même condition. Plusieurs « afficher » sur le même champ : une seule condition suffit. « Masquer » l’emporte si sa condition est remplie.', 'cf7-magic-wizard'); ?>
        </p>
    </div>
</div>
