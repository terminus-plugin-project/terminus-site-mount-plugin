#!/usr/bin/env bats

#
# test-mount.bats
#
# Test plugin 'mount' command
#

@test "output of plugin 'mount' command" {
  run terminus site:mount $TERMINUS_SITE.dev
  [[ "$output" == *"[notice] Site mounted in '/tmp/{$TERMINUS_SITE}.dev' successfully."* ]]
  [ "$status" -eq 0 ]
}
