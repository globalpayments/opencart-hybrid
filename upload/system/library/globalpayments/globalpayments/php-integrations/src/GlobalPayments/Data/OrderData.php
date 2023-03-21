<?php

namespace GlobalPayments\PaymentGatewayProvider\Data;

class OrderData {
	/**
	 * The amount to transfer between Payer and Merchant for a SALE or a REFUND. It is always represented in the lowest
	 * denomination of the related currency.
	 * e.g. 11099
	 *
	 * @var string
	 */
	public $amount;

	/**
	 * The currency of the amount in ISO-4217(alpha-3).
	 * e.g. USD
	 *
	 * @var string
	 */
	public $currency;

	/**
	 * Merchant defined field to reference the transaction.
	 *
	 * @var string
	 */
	public $reference;

	/**
	 * Merchant defined field to describe the transaction.
	 * e.g. SKU#BLK-MED-G123-GUC
	 *
	 * @var string
	 */
	public $description;

	/**
	 * The merchant's payer reference for the transaction.
	 * e.g. CUST_12345
	 *
	 * @var string
	 */
	public $payerReference;

	/**
	 * Merchant defined field common to all transactions that are part of the same order.
	 * e.g. INV#88547
	 *
	 * @var string
	 */
	public $orderReference;

	/**
	 * @var \GlobalPayments\Api\Entities\Address
	 */
	public $billingAddress;

	/**
	 * @var \GlobalPayments\Api\Entities\Address
	 */
	public $shippingAddress;

	/**
	 * @var string
	 */
	public $customerEmail;
}
