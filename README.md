# Wallet Auth Module

A Drupal 10+ module providing wallet-based authentication using [Wallet as a Protocol (WaaP)](https://docs.wallet.human.tech/) and [Sign-In with Ethereum (SIWE/EIP-4361)](https://eips.ethereum.org/EIPS/eip-4361).

## Why WaaP?

[Wallet as a Protocol (WaaP)](https://docs.wallet.human.tech/) enables true self-custody without seed phrases. Unlike Wallet-as-a-Service where wallets are rented, WaaP gives users:

- **Universal access** - No browser extension required
- **Social login** - Email, phone, Google, Twitter, Discord
- **True ownership** - Wallets are free and portable across apps
- **No seed phrases** - Trustless recovery via human attributes

See [What is WaaP?](https://docs.wallet.human.tech/) to learn more.

## Features

- Wallet-based authentication (no passwords)
- Automatic user creation on first authentication
- Support for Ethereum, Polygon, Arbitrum, Optimism, BSC, Sepolia
- Multiple wallets per user
- User management via People administration

## Requirements

- Drupal 10.6+ or Drupal 11
- PHP 8.2+
- [External Auth](https://www.drupal.org/project/externalauth) module

## Installation

1. Copy this module to `web/modules/custom/wallet_auth/`
2. Enable the module: `drush pm:enable wallet_auth -y`
3. Clear caches: `drush cr`
4. Configure at `/admin/config/people/wallet-auth`

**That's it!** The module automatically adds a "Sign In" link to your User Account Menu. No block placement required.

### Optional: Wallet Login Block

For more flexibility, a standalone Wallet Login Block is also available:

1. Navigate to `/admin/structure/block`
2. Click "Place block" and find "Wallet Login Button"
3. Configure display mode (link or button) and visibility
4. Click "Save block"

## Configuration

Configure settings at `/admin/config/people/wallet-auth`:

- **Blockchain network** - Select the Ethereum-compatible network (Mainnet, Sepolia, Polygon, BSC, Arbitrum, Optimism)
- **Sign-in button text** - Customize the text shown on the sign-in link/button
- **Display mode** - Choose between "Link" (matches navigation styling) or "Button" (uses theme button styling)
- **Authentication methods** - Enable email, phone, social login options
- **Allowed social providers** - Choose which social logins to offer (Google, Twitter, Discord)
- **Nonce lifetime** - How long authentication nonces remain valid (60-3600 seconds, default: 300)

## Usage

### For End Users

1. Click "Sign In" in the User Account Menu (or Wallet Login block if placed)
2. Choose an authentication method (email, phone, social, or wallet)
3. Complete authentication in the modal
4. Approve the signature request (if using a crypto wallet)
5. You're automatically logged in to Drupal

**Note:** The "Sign In" link only displays for anonymous users. Authenticated users see "My account" and "Log out" instead.

### User Account Creation

When a user authenticates with their wallet for the first time:

- A new Drupal account is automatically created
- Username format: `wallet_0x1234...` (truncated address)
- The wallet address is linked to the account
- User is logged in immediately

Subsequent authentications with the same wallet will log in the existing account.

### For Administrators

Manage wallet addresses at **People → Wallets** (`/admin/people/wallets`)

From this page you can:

- View all wallet addresses linked to user accounts
- Edit wallet address ownership
- Reassign orphaned wallets to different users (when a user account is deleted)
- Enable/disable wallet addresses

## Security

This module implements industry-standard security practices:

- EIP-191/EIP-4361 compliant signature verification
- Cryptographically secure single-use nonces
- Nonces expire after 5 minutes (configurable)
- No private keys stored - only wallet addresses
- All inputs validated and sanitized
- SQL injection prevention via parameterized queries
- XSS prevention via output escaping
- CSRF protection via signature verification
- Replay attack prevention via nonce expiration

## License

GPL-2.0-or-later

## Contributing

Contributions are welcome! Please see [CLAUDE.md](CLAUDE.md) for development documentation, API reference, and testing guidelines.

## Links

- [WaaP Documentation](https://docs.wallet.human.tech/)
- [Sign-In with Ethereum (EIP-4361)](https://eips.ethereum.org/EIPS/eip-4361)
- [SIWE Documentation](https://docs.login.xyz/)
