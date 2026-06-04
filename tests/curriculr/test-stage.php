<?php
require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

gsh_assert_eq( gsh_tp_curriculr_normalize_stage( 'entwurf' ), 'entwurf', 'entwurf stays' );
gsh_assert_eq( gsh_tp_curriculr_normalize_stage( 'genehmigt' ), 'genehmigt', 'genehmigt stays' );
gsh_assert_eq( gsh_tp_curriculr_normalize_stage( 'oeffentlich' ), 'oeffentlich', 'oeffentlich stays' );
gsh_assert_eq( gsh_tp_curriculr_normalize_stage( 'bogus' ), 'entwurf', 'unknown -> entwurf' );
gsh_assert_eq( gsh_tp_curriculr_normalize_stage( null ), 'entwurf', 'null -> entwurf' );
gsh_assert_eq( gsh_tp_curriculr_normalize_stage( 'OEFFENTLICH' ), 'oeffentlich', 'case-insensitive' );
gsh_test_done();
