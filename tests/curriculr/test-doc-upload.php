<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

$sample = file_get_contents( __DIR__ . '/fixtures/sample-doc.json' );

$ok = gsh_tp_curriculr_decode_doc_upload( $sample );
gsh_assert_eq( $ok['valid'], true, 'valid sample doc passes' );
gsh_assert_true( is_array( $ok['doc'] ), 'decoded doc is array' );
gsh_assert_eq( $ok['doc']['meta']['name'], 'Terminplan 2026/27', 'decoded doc keeps meta.name' );

$bad_json = gsh_tp_curriculr_decode_doc_upload( '{not valid json' );
gsh_assert_eq( $bad_json['valid'], false, 'invalid JSON fails' );
gsh_assert_contains( implode( ',', $bad_json['errors'] ), 'invalid_json', 'reports invalid_json' );

$no_events = gsh_tp_curriculr_decode_doc_upload( json_encode( array( 'meta' => array( 'name' => 'x' ) ) ) );
gsh_assert_eq( $no_events['valid'], false, 'doc without events array fails' );
gsh_assert_contains( implode( ',', $no_events['errors'] ), 'doc_events_missing', 'reports doc_events_missing' );

$not_object = gsh_tp_curriculr_decode_doc_upload( '"just a string"' );
gsh_assert_eq( $not_object['valid'], false, 'JSON scalar (non-object) fails' );

gsh_test_done();
