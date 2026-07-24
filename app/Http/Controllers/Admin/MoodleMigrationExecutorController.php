<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class MoodleMigrationExecutorController extends Controller
{
    public function index()
    {
        return view('admin.moodle-tools');
    }

    public function runAction(Request $request, $action)
    {
        $output = '';

        if ($action === 'moodle-recon') {
            $output = $this->runScript('recon');
        } elseif ($action === 'moodle-migrate') {
            $output = $this->runScript('run --confirm');
        } elseif ($action === 'moodle-fix-old') {
            $output = $this->runScript('fix-old --confirm');
        } elseif ($action === 'import-moodle-users') {
            $output = $this->runArtisan('moodle:import-users');
        } elseif ($action === 'import-joomla-users') {
            $output = $this->runArtisan('db:seed', ['--class' => 'JoomlaUserImportSeeder']);
        } else {
            return response()->json(['error' => 'Invalid action'], 400);
        }

        return response()->json(['output' => $output]);
    }

    protected function runScript($args)
    {
        $scriptPath = base_path('scripts/moodle/moodle-migrate.php');
        if (!file_exists($scriptPath)) {
            return "Script not found at: $scriptPath";
        }

        $php = PHP_BINARY;
        $cmd = sprintf('%s %s %s 2>&1', escapeshellarg($php), escapeshellarg($scriptPath), $args);
        
        $output = [];
        $rc = 0;
        exec($cmd, $output, $rc);

        return implode("\n", $output) . "\nExit Code: $rc";
    }

    protected function runArtisan($command, $args = [])
    {
        $output = new BufferedOutput();
        try {
            $rc = Artisan::call($command, $args, $output);
            return $output->fetch() . "\nExit Code: $rc";
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}
