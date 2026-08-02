<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ApplicationFormExperienceTest extends TestCase
{
    public function test_application_and_user_create_edit_forms_use_the_new_shared_frame(): void
    {
        $root = dirname(__DIR__, 2).'/app/Filament/Resources/';

        foreach ([
            'Applications/Schemas/ApplicationForm.php',
            'Applications/Schemas/AclMixApplicationForm.php',
            'Applications/Schemas/LotteFinanceApplicationForm.php',
            'Users/Schemas/UserForm.php',
        ] as $screen) {
            $source = file_get_contents($root.$screen);

            self::assertStringContainsString("->extraAttributes(['class' => 'crm-record-form-frame'])", $source, $screen);
        }

        $acl = file_get_contents($root.'Applications/Schemas/AclMixApplicationForm.php');
        self::assertStringContainsString('AclMixWorkflow::statusLabel($state)', $acl);
    }

    public function test_form_frame_has_highlighted_sections_fields_and_sticky_actions(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Providers/Filament/AdminPanelProvider.php',
        );
        $start = strpos($source, '/* Record forms: clear sections');
        $end = strpos($source, '/* Record view: keep the chrome stable', $start);
        $styles = substr($source, $start, $end - $start);

        self::assertStringContainsString('.crm-record-form-frame .fi-section-header', $styles);
        self::assertStringContainsString('background: linear-gradient(100deg, #e8f5ff', $styles);
        self::assertStringContainsString('.crm-record-form-frame .fi-input-wrp:focus-within', $styles);
        self::assertStringContainsString('.fi-form-actions {', $styles);
        self::assertStringContainsString('.crm-record-form-frame .fi-tabs,', $styles);
        self::assertStringContainsString('.crm-record-form-frame .fi-sc-wizard-header', $styles);
        self::assertStringContainsString('.crm-record-form-frame .fi-sc-wizard-footer', $styles);
        self::assertStringContainsString('position: sticky !important', $styles);
        self::assertStringContainsString('bottom: 10px !important', $styles);
        self::assertSame(substr_count($styles, '{'), substr_count($styles, '}'));
    }
}
