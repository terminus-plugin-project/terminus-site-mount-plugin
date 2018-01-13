<?php

namespace TerminusPluginProject\TerminusSiteMount\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Collections\Sites;

// Get HOME directory.
define('TERMINUS_SITE_MOUNT_HOME', getenv('HOME'));

// Determine if the environment is Windows.
define('TERMINUS_SITE_MOUNT_WINDOWS', (php_uname('s') == 'Windows NT'));

// Set the default mount location.
define('TERMINUS_SITE_MOUNT_DIR', TERMINUS_SITE_MOUNT_HOME);

// Set the default drive letter.
define('TERMINUS_SITE_MOUNT_DRIVE', 'first');

/**
 * Class SiteMountCommand
 * Mounts/unmounts the site environment via SSHFS/SFTP.
 */
class SiteMountCommand extends TerminusCommand implements SiteAwareInterface
{

    use SiteAwareTrait;

    /**
     * Mounts the site environment via SSHFS/SFTP.
     *
     * @authorize
     *
     * @command site:mount
     * @aliases mount
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option dir Directory to mount (Linux / Mac only)
     * @option drive Drive letter to mount (Windows only)
     *
     * @usage terminus site:mount|mount <site-name>.<env> [--dir=<directory> --drive=<drive letter>]
     */
    public function mount($site_env = '', $options = ['dir' => TERMINUS_SITE_MOUNT_DIR, 'drive' => TERMINUS_SITE_MOUNT_DRIVE])
    {
        $sftp = $this->checkRequirements();

        if (empty($site_env)) {
            $message = "Usage: terminus site:mount|mount <site-name.env> [--dir=<directory> --drive=<drive letter>]";
            throw new TerminusNotFoundException($message);
        }

        // Determine connection information.
        list(, $env) = $this->getSiteEnv($site_env);
        $connection_info = $env->sftpConnectionInfo();
        $user = $connection_info['username'];
        $host = $connection_info['host'];
        $port = $connection_info['port'];

        // Execute the command(s).
        if (TERMINUS_SITE_MOUNT_WINDOWS) {
            // Set the profile configuration file.
            $username = getenv('USERNAME');
            $config = "/Users/{$username}/AppData/Roaming/SftpNetDrive/{$site_env}.cfg";

            // Create the profile if it doesn't exist.
            if (!file_exists("{$config}")) {
                $command = "\"{$sftp}\" new /profile:{$site_env} /server:{$host} /port:{$port} /login:{$user} /letter:{$options['drive']} /open /access:private /reconnect:3 /keepalive:5 /compression:1 /timeout:60";
                $this->execute($command);
            }

            // Set the drive letter.
            if ($options['drive'] != TERMINUS_SITE_MOUNT_DRIVE) {
                $command = "\"{$sftp}\" set /profile:{$site_env} /letter:{$options['drive']}";
                $this->execute($command);
            }

            // Start the profile.
            $command = "start \"\" \"{$sftp}\" start /profile:{$site_env} > nul 2> nul";

            // Set the message.
            $message = "Site mounted successfully.  Unmount with 'terminus umount {$site_env}'.";
        } else {
            // Determine the mount location.
            $mount = str_replace('~', TERMINUS_SITE_MOUNT_HOME, $options['dir']) . DIRECTORY_SEPARATOR . $site_env;

            // Create the mount directory if it doesn't exist.
            if (!file_exists("{$mount}")) {
                $command = "mkdir \"{$mount}\"";
                $this->execute($command);
            }

            // Abort if unable to create the mount directory.
            if (!file_exists("{$mount}")) {
                $message = "Unable to mount in '{$mount}'.";
                throw new TerminusNotFoundException($message);
            }

            $command = "sudo sshfs -o Port={$port} -o allow_other {$user}@{$host}:. {$mount}";

            // Set the message.
            $unmount = 'terminus umount ';
            $temp_mount_dir = TERMINUS_SITE_MOUNT_DIR . DIRECTORY_SEPARATOR . $site_env;
            $unmount .= ($mount == $temp_mount_dir) ? $site_env : $site_env . ' --dir=' . $options['dir'];
            $message = "Site mounted in '{$mount}' successfully.  Unmount with '{$unmount}'.";
        }

        // Execute the command to mount.
        $this->execute($command);

        // Output the final message.
        $this->log()->notice($message);
    }

    /**
     * Unmounts the site environment.
     *
     * @authorize
     *
     * @command site:umount
     * @aliases site:unmount umount unmount
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option dir Directory to unmount
     *
     * @usage terminus site:umount|site:unmount|umount|unmount <site-name>.<env> [--dir=<directory>]
     */
    public function unmount($site_env = '', $options = ['dir' => TERMINUS_SITE_MOUNT_DIR])
    {
        $sftp = $this->checkRequirements();

        if (empty($site_env)) {
            $message = "Usage: terminus site:umount|site:unmount|umount|unmount <site-name.env> [--dir=<directory>]";
            throw new TerminusNotFoundException($message);
        }

        if (TERMINUS_SITE_MOUNT_WINDOWS) {
            // Execute the command to unmount.
            $command = "\"{$sftp}\" stop /profile:{$site_env} /wait /timeout:30";
            $this->execute($command);
        } else {
            // Determine the mount location.
            $mount = str_replace('~', TERMINUS_SITE_MOUNT_HOME, $options['dir']) . DIRECTORY_SEPARATOR . $site_env;

            // Check if the directory exists.
            if (!file_exists("{$mount}")) {
                $message = "Site environment {$site_env} not mounted.";
                throw new TerminusNotFoundException($message);
            }

            // Cannot unmount inside a mounted directory.
            exec('pwd', $directory);
            $pwd = array_pop($directory);
            if (strpos($pwd, $mount) !== false) {
                $message = 'Please change to another directory outside the mounted location and try again.';
                throw new TerminusNotFoundException($message);
            }

            // Execute the command to unmount.
            $command = "sudo umount {$mount}";
            $this->execute($command);

            // Remove mount directory.
            $command = "rmdir {$mount}";
            $this->execute($command);
        }
    }

    /**
     * Platform independent check whether a command exists.
     *
     * @param string $command Command to check
     * @return bool True if exists, false otherwise
     */
    protected function commandExists($command)
    {
        if (TERMINUS_SITE_MOUNT_WINDOWS) {
            exec("cd / && dir /s /b {$command} 2> nul", $paths);
            $path = array_pop($paths);
            return file_exists("$path") ? $path : false;
        } else {
            $test_command = 'command -v';
            $file = popen("{$test_command} {$command}", 'r');
            $result = fgets($file, 255);
            return !empty($result);
        }
    }

    /**
     * Check for plugin requirements.
     */
    protected function checkRequirements()
    {
        if (TERMINUS_SITE_MOUNT_WINDOWS) {
            if (!$path = $this->commandExists('SftpNetDrive.exe')) {
                $release = 'See http://www.sftpnetdrive.com/products/NDXCA/download';
                $message = "Please install SftpNetDrive to enable site mounts.  {$release}.";
                throw new TerminusNotFoundException($message);
            } else {
                return $path;
            }
        } else {
            if (!$this->commandExists('mount')) {
                $message = 'Please install the mount command to enable site mounts.';
                throw new TerminusNotFoundException($message);
            }
            if (!$this->commandExists('umount')) {
                $message = 'Please install the umount command to enable site mounts.';
                throw new TerminusNotFoundException($message);
            }
            if (!$this->commandExists('sshfs')) {
                $release = 'See https://github.com/libfuse/sshfs/releases or install via your package manager';
                $message = "Please install sshfs to enable site mounts.  {$release}.";
                throw new TerminusNotFoundException($message);
            }
        }
    }

    /**
     * Executes the command.
     */
    protected function execute($cmd) {
        $process = proc_open(
            $cmd,
            [
                0 => STDIN,
                1 => STDOUT,
                2 => STDERR,
            ],
            $pipes
        );
        proc_close($process);
    }
}
