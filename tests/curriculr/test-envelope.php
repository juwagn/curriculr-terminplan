<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$ok = gsh_tp_curriculr_validate_envelope( array(
    'doc'         => array( 'events' => array() ),
    'baseVersion' => 0,
) );
gsh_assert_eq( $ok['valid'], true, 'valid envelope passes' );

$no_base = gsh_tp_curriculr_validate_envelope( array( 'doc' => array( 'events' => array() ) ) );
gsh_assert_eq( $no_base['valid'], false, 'missing baseVersion fails' );
gsh_assert_contains( implode( ',', $no_base['errors'] ), 'baseVersion_missing', 'reports baseVersion_missing' );

$no_doc = gsh_tp_curriculr_validate_envelope( array( 'baseVersion' => 1 ) );
gsh_assert_eq( $no_doc['valid'], false, 'missing doc fails' );

$no_events = gsh_tp_curriculr_validate_envelope( array( 'doc' => array( 'meta' => array() ), 'baseVersion' => 1 ) );
gsh_assert_eq( $no_events['valid'], false, 'doc without events array fails' );

$not_object = gsh_tp_curriculr_validate_envelope( 'nope' );
gsh_assert_eq( $not_object['valid'], false, 'non-array body fails' );
gsh_test_done();
