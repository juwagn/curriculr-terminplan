<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

gsh_assert_eq( gsh_tp_curriculr_version_decision( 0, 0 ), 'ok', 'first write: base 0 vs current 0 -> ok' );
gsh_assert_eq( gsh_tp_curriculr_version_decision( 5, 5 ), 'ok', 'matching versions -> ok' );
gsh_assert_eq( gsh_tp_curriculr_version_decision( 6, 5 ), 'conflict', 'server newer -> conflict' );
gsh_assert_eq( gsh_tp_curriculr_version_decision( 5, 7 ), 'conflict', 'client ahead (impossible) -> conflict' );
gsh_test_done();
