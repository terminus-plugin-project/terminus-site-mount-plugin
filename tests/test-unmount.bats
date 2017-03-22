#!/usr/bin/env bats

#
# test-unmount.bats
#
# Test plugin 'unmount' command
#

@test "output of plugin 'unmount' command" {
  run terminus site:unmount $TERMINUS_SITE.dev
  [ "$status" -eq 0 ]
}
