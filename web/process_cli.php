<?php 
/**
 * Command-line version of process.php to be run as a background. Usage:
 * 
 * php process_cli.php RAW_FILE ANNOTATION_FILE VARIANT_TYPE SET_ID
 */


require_once 'lib/init.php';
require_once 'lib/util.php';
require_once 'lib/cfio.php';
require_once 'lib/rest.php';

/**
* Step 1/6: Write files
*
* @param int $proc_id
* @param string $vcf_file
* @return bool
*/
function write_files($working_dir, $uploaded_file, $set_id, $vcf_file)
{
    global $vat_config;

    if ( ! mkdir($working_dir . '/vat.' . $set_id))
    {
        array_push($fatal_error, "Could not mkdir directory for file");
        return FALSE;
    }

    if (($retval = rename($working_dir . '/' . $uploaded_file, $working_dir . '/vat.' . $set_id . ".raw.vcf")) === FALSE)
    {
        return FALSE;
    }

    return TRUE;
}

/**
 * Step 2/6: Annotating variants
 *
 * @param int $proc_id
 * @param string $program
 * @param string $annotation_file
 * @return bool
 */
function annotate_variants($working_dir, $set_id, $program, $annotation_file)
{
    global $vat_config, $fatal_error, $vat_upload_debug;

    if ($program == 'svMapper')
    {
        $cmd = sprintf('%s/%s %s/%s.interval < %s/vat.%d.raw.vcf > %s/vat.%d.vcf 2>&1',
                        $vat_config['VAT_EXEC_DIR'], $program,
                        $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file,
                        $working_dir, $set_id,
                        $working_dir, $set_id);
    }
    else
    {
        $cmd = sprintf('%s/%s %s/%s.interval %s/%s.fa < %s/vat.%d.raw.vcf > %s/vat.%d.vcf 2>&1',
                        $vat_config['VAT_EXEC_DIR'], $program,
                        $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file,
                        $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file,
                        $working_dir, $set_id,
                        $working_dir, $set_id);
    }

    exec($cmd, $output, $retval);

    return $retval == 0;
}

/**
 * Step 3/6: Index files
 *
 * @param int $proc_id
 * @return bool
 */
function index_files($working_dir, $set_id)
{
    global $vat_config;

    $cmd = sprintf('%s/bgzip -c %s/vat.%d.vcf > %s/vat.%d.vcf.gz 2>&1',
                    $vat_config['TABIX_DIR'],
                    $working_dir, $set_id,
                    $working_dir, $set_id);

    exec($cmd, $output, $retval);


    if ($retval != 0)
    {
        return FALSE;
    }

    $cmd = sprintf('%s/tabix -p vcf %s/vat.%d.vcf.gz 2>&1',
                    $vat_config['TABIX_DIR'],
                    $working_dir, $set_id);
     
    exec($cmd, $output, $retval);


    return $retval == 0;
}


/**
 * Step 4/6: Create summary files
 *
 * @param int $proc_id
 * @param string $annotation_file
 * @return bool
 */
function create_summary_file($working_dir, $set_id, $annotation_file)
{
    global $vat_config;

    $cmd = sprintf('%s/vcfSummary %s/vat.%d.vcf.gz %s/%s.interval 2>&1',
                    $vat_config['VAT_EXEC_DIR'],
                    $working_dir, $set_id,
                    $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file);

    exec($cmd, $output, $retval);

    return $retval == 0;
}

/**
 * Steph 5/6: Generate images
 *
 * @param int $proc_id
 * @param string $annotation_file
 * @return bool
 */
function generate_images($working_dir, $set_id, $annotation_file)
{
    global $vat_config;

    $cmd = sprintf('%s/vcf2images %s/vat.%d.vcf.gz %s/%s.interval %s/vat.%d 2>&1',
                    $vat_config['VAT_EXEC_DIR'],
                    $working_dir, $set_id,
                    $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file,
                    $working_dir, $set_id);

    exec($cmd, $output, $retval);

    return $retval == 0;
}

/**
 * Step 6/6: Subset file
 *
 * @param int $param_id
 * @param string $annotation_file
 * @return bool
 */
function subset_file($working_dir, $set_id, $annotation_file)
{
    global $vat_config;

    $cmd = sprintf('%s/vcfSubsetByGene %s/vat.%d.vcf.gz %s/%s.interval %s/vat.%d 2>&1',
                    $vat_config['VAT_EXEC_DIR'],
                    $working_dir, $set_id,
                    $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file,
                    $working_dir, $set_id);

    exec($cmd, $output, $retval);

    return $retval == 0;
}


if (count($argv) != 5)
{
    die("Usage: php process_cli.php RAW_FILE ANNOTATION_FILE VARIANT_TYPE SET_ID");
}

$raw_file        = $argv[1];
$annotation_file = $argv[2];
$variant_type    = $argv[3];
$set_id          = $argv[4];

$program = $variant_type;

$cfio = new CFIO();
$cfio->set_set_id($set_id);

$working_dir = $cfio->get_working_dir();

$url = 'http://' . $vat_config['MASTER_ADDRESS'] . '/dataset_api.php';

try
{
    $cfio->get_raw($raw_file);
}
catch (Exception $e)
{
    die("Could not get input file " . $_GET['inFile'] . ": " . $e->getMessage());
}


// Step 1: write files
if (write_files($working_dir, $raw_file, $set_id, $raw_file) === FALSE)
{
    die("write_files: failed");
}
else
{
    echo "Make request to master with status " . SET_STATUS_WRITE_FILES . "\n";
    $request_body = array(
        'id' => 1,
        'data' => '{ "status" : ' . SET_STATUS_WRITE_FILES .' }'
    );
    $request = new RESTTxRequest($url, 'PUT', $request_body);
    $request->execute();
    
}


// Step 2: annotate variants
if (annotate_variants($working_dir, $cfio->get_set_id(), $program, $annotation_file) === FALSE)
{
    die("annotate_variants: failed");
}
else
{
    echo "Make request to master with status " . SET_STATUS_ANNOTATE_VARIANTS . "\n";
    $request_body = array(
        'id' => $set_id,
        'data' => '{ "status" : ' . SET_STATUS_ANNOTATE_VARIANTS .' }'
    );
    $request = new RESTTxRequest($url, 'PUT', $request_body);
    $request->execute();
}

// Step 3: index files
if (index_files($working_dir, $cfio->get_set_id()) === FALSE)
{
    die("index_files: failed");
}
else
{
    echo "Make request to master with status " . SET_STATUS_INDEX_FILES . "\n";
    $request_body = array(
        'id' => $set_id,
        'data' => '{ "status" : ' . SET_STATUS_INDEX_FILES .' }'
    );
    $request = new RESTTxRequest($url, 'PUT', $request_body);
    $request->execute();
}

// Step 4: create summary files
if (create_summary_file($working_dir, $cfio->get_set_id(), $annotation_file) === FALSE)
{
    die("create_summary_file: failed");
}
else
{
    echo "Make request to master with status " . SET_STATUS_SUMMARY . "\n";
    $request_body = array(
        'id' => $set_id,
        'data' => '{ "status" : ' . SET_STATUS_SUMMARY .' }'
    );
    $request = new RESTTxRequest($url, 'PUT', $request_body);
    $request->execute();
}

// Step 5: generate images
if (generate_images($working_dir, $cfio->get_set_id(), $annotation_file) === FALSE)
{
    die("generate images: failed");
}
else
{
    echo "Make request to master with status " . SET_STATUS_GENERATE_IMAGES . "\n";
    $request_body = array(
        'id' => $set_id,
        'data' => '{ "status" : ' . SET_STATUS_GENERATE_IMAGES .' }'
    );
    $request = new RESTTxRequest($url, 'PUT', $request_body);
    $request->execute();
}


// Step 6: Subset file
if (subset_file($working_dir, $cfio->get_set_id(), $annotation_file) === FALSE)
{
    die('subset file: failed');
}
else
{
    echo "Make request to master with status " . SET_STATUS_SUBSET . "\n";
    $request_body = array(
        'id' => $set_id,
        'data' => '{ "status" : ' . SET_STATUS_SUBSET .' }'
    );
    $request = new RESTTxRequest($url, 'PUT', $request_body);
    $request->execute();
}

// Step last: sync files
try
{
    $cfio->push_data();
    $cfio->delete_raw($raw_file);
}
catch (Exception $e)
{
    die("sync files failed: " . $e->getMessage());
}

echo "Make request to master with status " . SET_STATUS_COMPLETE . "\n";
$request_body = array(
    'id' => $set_id,
    'data' => '{ "status" : ' . SET_STATUS_COMPLETE .' }'
);
$request = new RESTTxRequest($url, 'PUT', $request_body);
$request->execute();


?>