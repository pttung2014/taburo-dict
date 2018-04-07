<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppController', 'Controller');

/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class DictionariesController extends AppController {

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array('Original','Tran');

/**
 * Displays a view
 *
 * @return void
 * @throws NotFoundException When the view file could not be found
 *	or MissingViewException in debug mode.
 */
	public function index() {

		/*
		echo '<pre>';//TODO

		$textSearch = 'absolute';
		$this->Original->recursive = -1;
		$dataSearch = $this->Original->searchWord($textSearch);
		print_r($dataSearch);


		$id = 946;//TODO

		$this->Original->recursive = 1;
		$data = $this->Original->getOriginalById($id);



		
		print_r($data);//TODO
		
		$this->Tran->recursive = 1;
		$data = $this->Tran->getTransByOriginalId($id);
		print_r($data);//TODO
		exit;

		*/
	}
}
