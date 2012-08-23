<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon Web Services Library
 *  @package     paws
 *  @copyright   Copyright (c) 2008 Bruce E. Wampler
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2008-06-19
 */
/******************************************************************************* 
 *   _  _  _  _
 *  (p)(a)(w)(s)
 *   '  '  '  '
 *   PHP Amazon Web Services Library
 *   A PHP oriented Library for AWS Simple DB, S3, and EC2.
 *
 */
/* This is an example of how to create an "initialization" SDB database dump file.
 * It is sometimes necessary or useful to build a basic database with some initial
 * entries. It is possible to work directly with the paws/SDB dump format, which consists
 * of PHP serialized data, but using PHP array definitions is much more human readable.
 *
 * This creates an array of arrays that can then be written to a paws/SDB dump format file.
 *
 * Note: as usual, paws does not enforce limits on number of domains, number of items,
 * number of attributes, or sizes of anything.
 *
 * To create a dump file of this example, simply run php from a terminal window:
 *    php createSDBexample.php > filename.sdb
 *
 * You can then use the pawsLoadSDB.php file to create the database:
 *
 *     php pawsLoadSDB.php < filename.sdb
 *
 **/
$thisPawsSDB = array(
    array('pawsBeginSDB'=>'example DB'),
    array('pawsComment'=>'This allows a comment field to give dates or whatever is needed.'),
    array('pawsBeginDomain'=>'testDomain1'),
	array('pawsItemName'=>'item01'),
	    array('N1'=>'V1','N2'=>'V2','N3m'=>array('v3a','v3b','v3c'),'N4'=>'v4 will be longer for now.'),
	array('pawsItemName'=>'item02'),
	    array('N1'=>'v2','N2'=>'val2','N3m'=>'onlyone','N4'=>'shorter this time'),
	array('pawsItemName'=>'item03'),
	    array('N1'=>'v2abc','N2'=>'val44','N3m'=>array('a','b','c'),'N4'=>'something else'),
    array('pawsEndDomain'=>'testDomain1'),
    array('pawsBeginDomain'=>'testDomain2'),
	array('pawsItemName'=>'2item01'),
	    array('2N1'=>'2V1','2N2'=>'2V2','2N3m'=>array('2v3a','2v3b','2v3c'),'2N4'=>'2v4 will be longer for now.'),
	array('pawsItemName'=>'2item02'),
	    array('2N1'=>'2v2','2N2'=>'2val2','2N3m'=>'2onlyone','2n4'=>'2shorter this time'),
    array('pawsEndDomain'=>'testDomain2'),
    array('pawsEndSDB'=>'example DB')
);

foreach ($thisPawsSDB as $lineout){
    echo serialize($lineout) . "\n";
}
?>
