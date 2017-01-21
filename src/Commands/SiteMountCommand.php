<?php

namespace TerminusPluginProject\TerminusSiteMount\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Collections\Sites;

/**
 * Class SiteMountCommand
 * Mounts/unmounts the site environment via SSHFS.
 */
class SiteMountCommand extends TerminusCommand implements SiteAwareInterface
{

    use SiteAwareTrait;

    /**
     * Mounts the site environment via SSHFS.
     *
     * @authorize
     *
     * @command site:mount
     * @aliases mount

     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage terminus site:mount <site>.<env>
     */
    public function mount($site_env = '')
    {
        $this->checkRequirements();

        if (empty($site_env)) {
            $message = "Usage: terminus site:mount|mount <site-name.env>";
            throw new TerminusNotFoundException($message);
        }

        // Determine connection information.
        list($site, $env) = $this->getSiteEnv($site_env);
        $connection_info = $env->sftpConnectionInfo();
        $user = $connection_info['username'];
        $host = $connection_info['host'];
        $port = $connection_info['port'];

        // Determine the mount location.
        $windows = (php_uname('s') == 'Windows NT');
        $mount = $windows ? "\\Temp\\{$host}" : "/tmp/{$host}";

        // Create the mount directory if it doesn't exist.
        $command = "if [ ! -d {$mount} ]; then mkdir {$mount}; fi";
        exec($command, $messages);
        foreach ($messages as $message) {
            $this->log()->notice($message);
        }

        // Execute the sshfs command.
        $command = "sshfs -o Port={$port} {$user}@{$host}:. {$mount}";
        exec($command, $messages);
        foreach ($messages as $message) {
            $this->log()->notice($message);
        }

        // Output mounted files location message.
        $message = "Type 'cd {$mount}' to view the mounted files.";
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
     *
     * @usage terminus site:umount <site>.<env>
     */
    public function unmount($site_env = '')
    {
        $this->checkRequirements();

        if (empty($site_env)) {
            $message = "Usage: terminus site:umount|site:unmount|umount|unmount <site-name.env>";
            throw new TerminusNotFoundException($message);
        }

        // Determine connection information.
        list($site, $env) = $this->getSiteEnv($site_env);
        $connection_info = $env->sftpConnectionInfo();
        $host = $connection_info['host'];

        // Determine the mount location.
        $windows = (php_uname('s') == 'Windows NT');
        $mount = $windows ? "\\Temp\\{$host}" : "/tmp/{$host}";

        // Cannot unmount inside a mounted directory.
        exec('pwd', $directory);
        $pwd = array_pop($directory);
        if (strpos($pwd, $mount) !== false) {
            $message = 'Please change to another directory outside the mounted location and try again.';
            throw new TerminusNotFoundException($message);
        }

        // Execute the umount command.
        $command = "sudo umount {$mount}";
        exec($command, $messages);
        foreach ($messages as $message) {
            $this->log()->notice($message);
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
        // @TODO: This could be a generic utility function used by other commands.

        $windows = (php_uname('s') == 'Windows NT');
        $test_command = $windows ? 'where' : 'command -v';
        $file = popen("$test_command $command", 'r');
        $result = fgets($file, 255);
        return $windows ? !preg_match('#Could not find files#', $result) : !empty($result);
    }

    /**
     * Check for plugin requirements.
     */
    protected function checkRequirements()
    {
        $windows = (php_uname('s') == 'Windows NT');
        if (!$this->commandExists('mount')) {
            $message = 'Please install the mount command to enable site mounts.';
            throw new TerminusNotFoundException($message);
        }
        if (!$this->commandExists('umount')) {
            $message = 'Please install the umount command to enable site mounts.';
            throw new TerminusNotFoundException($message);
        }
        if (!$this->commandExists('sshfs')) {
            $release = $windows ? 'See https://linhost.info/2012/09/sshfs-in-windows/.' : 'See https://github.com/libfuse/sshfs/releases or install via your package manager.';
            $message = "Please install sshfs to enable site mounts.  {$release}";
            throw new TerminusNotFoundException($message);
        }
    }
}