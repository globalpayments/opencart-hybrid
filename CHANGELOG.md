<a href="https://github.com/globalpayments" target="_blank">
    <img src="https://avatars.githubusercontent.com/u/25797248?s=200&v=4" alt="Global Payments logo" title="Global Payments" align="right" width="225" />
</a>

# Changelog

## Latest Version - v1.7.1 (09/10/25)
### Bug Fixes:
- Fixed bug causing transaction failures when using TransIT gateway
- Includes the missing COF fields while card is being saved

## Latest Version - v1.7.0 (04/09/25)
### Enhancements:
- Added Blik and open banking payment method functionality for Poland merchants
- Various security updates
### Bug Fixes:
- Fixed gateway request format error

## Latest Version - v1.6.8 (16/07/25)
### Bug Fixes:
- UI error and GPAPI portal link in admin

## Latest Version - v1.6.7 (14/07/25)
- Added dropdown for mandatory AccountNames in admin configuration

## Latest Version - v1.6.6 (05/01/25)
- Added translation for spanish (MX)

## Latest Version - v1.6.5 (03/31/25)
- Fixed PHP deprecated warnings

## v1.6.4 (10/08/24)
- Fixed a bug where the 3DS result was not sent in the transaction request
- Added the possibility to configure the Transaction Processing Account Name

## v1.6.3 (04/30/24)
### Enhancements:
- Added french translations

## v1.6.2 (03/26/24)
### Enhancements:
- Implemented PayPal payment method
- Updated Hosted Fields JS Library version to 3.0.11

## v1.6.1 (02/27/24)
### Enhancements:
- Merged Sepa and Faster Payments into Bank Payment
- Added sort order for the payment methods

## v1.6.0 (12/07/23)
### Enhancements:
- Open Banking

## v1.5.2 (10/31/23)
### Bug Fixes:
- BNPL is unavailable for guest user

## v1.5.1 (10/19/23)
### Bug Fixes:
- GPI Transaction - Added supporting files

## v1.5.0 (10/12/23)
### Enhancements:
- GooglePay - configurable Allowed Card Auth Methods
- GPI Transaction - Added GPI Transaction gateway support

### Bug Fixes:
- Accepted cards field is now mandatory on the Apple Pay config

## v1.4.3 (08/08/23)
### Enhancements:
- Added the Card Holder Name in the Google Pay and Apple Pay requests

## v1.4.2 (06/22/23)
### Enhancements:
- Unified Payments - Added Credential Check button

## v1.4.0 (06/15/23)
### Enhancements:
- Unified Payments - Added Buy Now Pay Later

### Bug Fixes:
- Unified Payments - Fixed a bug where the Card Number iframe would not be 100% expanded on Mozilla Firefox

## v1.3.0 (05/23/23)
### Enhancements:
- Digital Wallets - Click to Pay

## v1.2.0 (10/27/22)
### Enhancements:
- Unified Payments - remove 3DS 1

## v1.1.0 (08/11/22)
### Enhancements:
- Unified Payments - added Card Holder Name for Hosted Fields
- Unified Payments - `Order Transaction Descriptor` is sent in authorize/charge requests
- Google Pay - added `Merchant Name` setting
- Google Pay - renamed `Global Payments Merchant ID` setting to `Global Payments Client ID`

## v1.0.0 (07/06/22)
### Enhancements:
- Unified Payments
- Added Admin option for Apple Pay button color
- Hosted Fields
- Sale transactions (automatic capture or separate capture action later)
- Refund transactions from a previous Sale
- Stored payment methods
- 3D Secure 2 & SCA
- 3D Secure 1
- Digital Wallets - Google Pay
- Digital Wallets - Apple Pay

---
