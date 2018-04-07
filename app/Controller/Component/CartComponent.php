<?php
class CartComponent extends Component {
	public $components = ['Session'];

	const MAX_QUANTITY = 99;

	public $controller;

	public $cartAlias = 'cartInfo';



	//==================================================================//
	public function __construct(ComponentCollection $collection, $settings = array()){
		$this->controller = $collection->getController();
		parrent::__construct($collection, array_merge($this->settings, (array) $setting));
	}

	//==================================================================//
	public function cart(){
		$quantity = 0;
		$totalWeight = 0;
		$subTotal = 0;
		$total = 0;
		$orderItemCount = 0;

		if($this->Session->check($this->cartAlias)){
			$cartInfo = $this->Session->read($this->cartAlias);
		} else {
			$cartInfo = [
				'quantity' => $quantity,
				'totalWeight' => $totalWeight,
				'subTotal' => $subTotal,
				'total' => $total,
				'orderItemCount' => $orderItemCount,
			];
		}
		
		if(!empty($cartInfo['OrderItem'])){
			foreach($cartInfo['OrderItem'] as $item){
				$quantity += $item['quantity'];
				$totalWeight += $item['totalWeight'];
				$subTotal += $item['subTotal'];
				$total += $item['subTotal'];
				$orderItemCount ++;
			}
			$cartInfo['quantity'] = sprintf('%01.2f', $quantity);
			$cartInfo['totalWeight'] = sprintf('%01.2f', $totalWeight);
			$cartInfo['subTotal'] = sprintf('%01.2f', $subTotal);
			$cartInfo['total'] = sprintf('%01.2f', $total);
			$cartInfo['orderItemCount'] = sprintf('%01.2f', $orderItemCount);
		}

		$this->Session->write($this->cartAlias, $cartInfo);
		return true;
	}

	//==================================================================//
	public function add($productId, $quantity=1){

		$product = ClassRegistry::init('Product')->find('first', [
			'recursive' => -1,
			'conditions' => [
				'Product.id' => $productId,
			]
		]);

		if(empty($product)){
			return false;
		}

		if(!is_numeric($quantity)){
			$quantity = 1;
		}

		if($quantity > self::MAX_QUANTITY){
			$quantity = self::MAX_QUANTITY;
		}

		if($quantity == 0){
			$this->remove($productId);
			return true;
		}

		$dataOrderItem = [
			'quantity' => $quantity,
			'weight' => $product['Product']['weight'],
			'product_id' => $product['Product']['id'],
			'total_weight' => sprintf('%01.2f', $product['Product']['weight'] * $quantity),
			'subtotal' => sprintf('%01.2f', $product['Product']['price'] * $quantity),
		];

		$dataOrderItem['Product'] = $product['Product'];

		$this->Session->write($this->cartAlias . '.OrderItem.' . $productId, $dataOrderItem);

		$this->cart();

		return true;
	}

	//==================================================================//
	public function remove($productId){
		if($this->Session->check($this->cartAlias . '.OrderItem.' . $productId)){
			$product = $this->Session->read($this->cartAlias . '.OrderItem.' . $productId);

			$this->Session->delete($this->cartAlias . '.OrderItem.' . $productId);
			$this->cart();

			return $product;
		}
		return false;
	}

	//==================================================================//


	//==================================================================//


	//==================================================================//


	//==================================================================//


	//==================================================================//

	//==================================================================//


	//==================================================================//


	//==================================================================//

}