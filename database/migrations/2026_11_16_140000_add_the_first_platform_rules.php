<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    // The platform's rules exist from the start (docs: Site Administration → Platform rules): a
    // migration, not a seeder, so that every database has them, production included. For now the one
    // default rule. Its text is kept in each language of the interface; later versions are added by
    // administrators, in the database.
    public function up(): void
    {
        if (DB::table('platform_rule_versions')->exists()) {
            return;
        }

        DB::table('platform_rule_versions')->insert([
            'id' => (string) Str::uuid(),
            'rules' => json_encode([[
                'text' => [
                    'en' => 'Forbidden content. Nobody may post, spread, approve of or look for content that breaks the taboos shared by all people: any sexualisation of children (drawn or made up included), incest, bestiality, necrophilia, sexual violence and intimate material shared without the person\'s consent, and relishing real torture, killing or cruelty to people or animals.',
                    'uk' => 'Заборонений вміст. Не можна публікувати, поширювати, схвалювати чи шукати вміст, що порушує загальнолюдські табу: будь-яка сексуалізація дітей (зокрема намальована чи вигадана), інцест, зоофілія, некрофілія, сексуальне насильство й інтимні матеріали без згоди людини, а також смакування реальних катувань, убивств і знущань над людьми чи тваринами.',
                ],
                'example' => null,
                'kind' => 'ban',
            ]], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }

    // Only while it is still the one version: by then administrators may have added their own.
    public function down(): void
    {
        if (DB::table('platform_rule_versions')->count() === 1) {
            DB::table('platform_rule_versions')->delete();
        }
    }
};
