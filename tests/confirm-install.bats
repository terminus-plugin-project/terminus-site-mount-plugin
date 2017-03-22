#!/usr/bin/env bats

#
# confirm-install.bats
#
# Ensure that Terminus and the Composer plugin have been installed correctly
#

@test "confirm terminus version" {
  terminus --version
}

@test "confirm mount version" {
  mount --version
}

@test "confirm umount version" {
  umount --version
}

@test "confirm sshfs version" {
  sshfs --version
}

@test "get help on plugin command" {
  run terminus help site:mount
  [[ "$output" == *"Mounts the site environment via SSHFS."* ]]
  [ "$status" -eq 0 ]
}
