<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Step 7c-2: guards that the backup scripts keep their safety rules (they cannot run MySQL here). */
class BackupScriptTest extends TestCase
{
    private function script(string $name): string
    {
        $path = base_path("bin/{$name}");
        $this->assertFileExists($path);

        return str_replace("\r\n", "\n", file_get_contents($path));
    }

    public function test_server_script_is_safe(): void
    {
        $sh = $this->script('backup.sh');

        $this->assertStringStartsWith("#!/bin/bash\n", $sh);
        $this->assertStringContainsString('set -euo pipefail', $sh);
        $this->assertStringContainsString('MYSQL_PWD="$DB_PASS" mysqldump', $sh, 'the password must go through the environment');
        $this->assertDoesNotMatchRegularExpression('/mysqldump[^\n]*\s-p/', $sh, 'never pass -p on the command line (visible in ps)');
        $this->assertStringContainsString('gzip -t', $sh);
        $this->assertStringContainsString('tar -tzf', $sh);
        $this->assertStringContainsString('umask 077', $sh);
        $this->assertStringContainsString('storage/app" private public', $sh);
        $this->assertStringContainsString('KEEP="${BACKUP_KEEP:-7}"', $sh);
        $this->assertDoesNotMatchRegularExpression('/echo[^\n]*DB_PASS|printf[^\n]*DB_PASS|say[^\n]*DB_PASS/', $sh, 'never print the password');
    }

    public function test_the_script_is_saved_with_unix_line_endings(): void
    {
        $this->assertStringNotContainsString("\r", file_get_contents(base_path('bin/backup.sh')));
    }

    public function test_pc_scripts_only_copy_files_named_like_our_backups_and_keep_them_private(): void
    {
        $pull = $this->script('pull-backup.ps1');
        $install = $this->script('install-pc-backup.ps1');

        $this->assertStringContainsString("'^(db|files)-\\d{4}-\\d{2}-\\d{2}_\\d{4}\\.(sql|tar)\\.gz$'", $pull);
        $this->assertStringContainsString('.part', $pull, 'a half-copied file must never look like a finished backup');
        $this->assertStringContainsString("{ 1000 } else { 100 }", $pull, 'an empty uploads archive is small but valid; a database dump never is');
        $this->assertStringContainsString('icacls', $install);
        $this->assertStringContainsString('-StartWhenAvailable', $install);
    }
}
