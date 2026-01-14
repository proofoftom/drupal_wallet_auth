<?php

declare(strict_types=1);

namespace Drupal\wallet_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure wallet authentication settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a SettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(
    $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    RequestContext $request_context,
  ) {
    parent::__construct($config_factory);
    $this->logger = $logger_factory->get('wallet_auth');
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('router.request_context'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wallet_auth.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wallet_auth_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('wallet_auth.settings');

    $form['network'] = [
      '#type' => 'select',
      '#title' => $this->t('Blockchain network'),
      '#description' => $this->t('Select the blockchain network to use for wallet authentication.'),
      '#options' => [
        'mainnet' => $this->t('Ethereum Mainnet'),
        'sepolia' => $this->t('Sepolia Testnet'),
        'polygon' => $this->t('Polygon'),
        'bsc' => $this->t('Binance Smart Chain'),
        'arbitrum' => $this->t('Arbitrum'),
        'optimism' => $this->t('Optimism'),
      ],
      '#default_value' => $config->get('network') ?? 'mainnet',
      '#required' => TRUE,
    ];

    $form['enable_auto_connect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable auto-connect'),
      '#description' => $this->t('Automatically attempt to connect the wallet when the block is loaded.'),
      '#default_value' => $config->get('enable_auto_connect') ?? TRUE,
    ];

    $form['nonce_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Authentication timeout'),
      '#description' => $this->t('How long the authentication challenge is valid in seconds. Default is 300 (5 minutes).'),
      '#default_value' => $config->get('nonce_lifetime') ?? 300,
      '#min' => 60,
      '#max' => 3600,
      '#required' => TRUE,
      '#field_suffix' => $this->t('seconds'),
    ];

    $form['authentication_methods'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Authentication methods'),
      '#description' => $this->t('Select which authentication methods to display.'),
      '#options' => [
        'email' => $this->t('Email'),
        'social' => $this->t('Social'),
      ],
      '#default_value' => $config->get('authentication_methods') ?? ['email', 'social'],
      '#required' => TRUE,
    ];

    $form['allowed_socials'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed social providers'),
      '#description' => $this->t('Select which social providers to allow.'),
      '#options' => [
        'google' => $this->t('Google'),
        'twitter' => $this->t('Twitter/X'),
        'discord' => $this->t('Discord'),
        'bluesky' => $this->t('Bluesky'),
      ],
      '#default_value' => $config->get('allowed_socials') ?? ['google', 'twitter', 'discord', 'bluesky'],
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="authentication_methods[social]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['redirect_on_success'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect path after login'),
      '#description' => $this->t('The internal Drupal path to redirect to after successful authentication (e.g., /user or /dashboard).'),
      '#default_value' => $config->get('redirect_on_success') ?? '/user',
      '#required' => TRUE,
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Filter out unchecked values from checkboxes.
    $authentication_methods = array_filter($form_state->getValue('authentication_methods'));
    $allowed_socials = array_filter($form_state->getValue('allowed_socials'));

    $this->config('wallet_auth.settings')
      ->set('network', $form_state->getValue('network'))
      ->set('enable_auto_connect', $form_state->getValue('enable_auto_connect'))
      ->set('nonce_lifetime', (int) $form_state->getValue('nonce_lifetime'))
      ->set('authentication_methods', array_values($authentication_methods))
      ->set('allowed_socials', array_values($allowed_socials))
      ->set('redirect_on_success', $form_state->getValue('redirect_on_success'))
      ->save();

    $this->logger->info('Wallet authentication settings updated.');
    parent::submitForm($form, $form_state);
  }

}
