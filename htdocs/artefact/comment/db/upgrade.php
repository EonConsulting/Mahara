<?php
/**
 *
 * @package    mahara
 * @subpackage artefact-comment
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

defined('INTERNAL') || die();

function xmldb_artefact_comment_upgrade($oldversion=0) {

    $success = true;

    if ($oldversion < 2011011201) {
        $table = new XMLDBTable('artefact_comment_comment');
        $field = new XMLDBField('rating');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED);

        $success = $success && add_field($table, $field);
    }

    if ($oldversion < 2013072400) {
        $table = new XMLDBTable('artefact_comment_comment');
        $field = new XMLDBField('lastcontentupdate');
        $field->setAttributes(XMLDB_TYPE_DATETIME);
        $success = $success && add_field($table, $field);

        $success = $success && execute_sql(
            'update {artefact_comment_comment} acc
            set lastcontentupdate = (
                select a.mtime
                from {artefact} a
                where a.id = acc.artefact
            )'
        );
    }

    if ($oldversion < 2015081000) {
        // Set default maxindent for threaded comments
        set_config_plugin('artefact', 'comment', 'maxindent', 5);
    }

    if ($oldversion < 2015100100) {
        // Add new column '' to table artefact_comment_comment used for diplaying comments by threads
        $table = new XMLDBTable('artefact_comment_comment');
        $field = new XMLDBField('threadedposition');
        $field->setAttributes(XMLDB_TYPE_INTEGER, 4, null, null);
        if (!field_exists($table, $field)) {
            log_debug('Updating position for threaded comments');
            add_field($table, $field);

            $index = new XMLDBIndex('threadedpositionix');
            $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('threadedposition'));
            add_index($table, $index);

            // Update the threaded position for all exising comments
            // We assume there is no child comment in the database before this release
            // Comments on views
            $commented_views = get_column_sql('
                SELECT DISTINCT onview
                FROM {artefact_comment_comment}
                WHERE onview IS NOT NULL
                ORDER BY onview
            ');
            if ($commented_views) {
                $total = count($commented_views);
                $limit = 5000;
                $done = 0;
                foreach ($commented_views as $v) {
                    $comments = get_records_sql_array('
                        SELECT artefact
                        FROM {artefact_comment_comment}
                        WHERE onview = ?
                        ORDER BY artefact
                        ', array($v));
                    $p = 1;
                    foreach ($comments as $c) {
                        update_record('artefact_comment_comment',
                            (object) array (
                                'threadedposition' => $p
                            ),
                            array (
                                'artefact' => $c->artefact
                            )
                        );
                        $p++;
                    }
                    $done++;
                    if (($done % $limit) == 0 || $done >= $total) {
                        log_debug("Updating comments on views: $done/$total");
                        set_time_limit(30);
                    }
                }
            }
            // Comments on artefact
            $commented_views = get_column_sql('
                SELECT DISTINCT onartefact
                FROM {artefact_comment_comment}
                WHERE onartefact IS NOT NULL
                ORDER BY onartefact
            ');
            if ($commented_views) {
                $total = count($commented_views);
                $limit = 5000;
                $done = 0;
                foreach ($commented_views as $v) {
                    $comments = get_records_sql_array('
                        SELECT artefact
                        FROM {artefact_comment_comment}
                        WHERE onartefact = ?
                        ORDER BY artefact
                        ', array($v));
                    $p = 1;
                    foreach ($comments as $c) {
                        update_record('artefact_comment_comment',
                            (object) array (
                                'threadedposition' => $p
                            ),
                            array (
                                'artefact' => $c->artefact
                            )
                        );
                        $p++;
                    }
                    $done++;
                    if (($done % $limit) == 0 || $done >= $total) {
                        log_debug("Updating comments on artefacts: $done/$total");
                        set_time_limit(30);
                    }
                }
            }
        }
    }

    return $success;
}
