<?php

namespace Drupal\beacon_billing\Controller;

use Stripe\Invoice;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\beacon_billing\BeaconBilling;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class InvoicesController.
 */
class InvoicesController extends ControllerBase {

  /**
   * Drupal\beacon_billing\BeaconBilling definition.
   *
   * @var \Drupal\beacon_billing\BeaconBilling
   */
  protected $beaconBilling;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new InvoicesController object.
   *
   * @param \Drupal\beacon_billing\BeaconBilling $beacon_billing
   *   The Beacon billing service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(BeaconBilling $beacon_billing, ConfigFactoryInterface $config_factory) {
    $this->beaconBilling = $beacon_billing;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('beacon_billing'),
      $container->get('config.factory')
    );
  }

  /**
   * Provide a list of recent invoices.
   */
  public function invoiceList() {
    // Fetch the invoices.
    $invoices = $this->beaconBilling->getInvoices();

    // Fetch the upcoming invoice.
    $upcoming_invoice = $this->beaconBilling->getUpcomingInvoice();

    // Check if the invoice calls failed.
    if (($invoices === FALSE) || ($upcoming_invoice === FALSE)) {
      drupal_set_message(t('An error occurred while loading your invoices. Please try again or contact support for assistance.'), 'error');
    }

    // Generate the invoice table header.
    $header = [
      t('Invoice number'),
      t('Date'),
      t('Period beginning'),
      t('Period ending'),
      t('Total'),
      t('Paid'),
    ];

    // Generate the build.
    $build = [
      'upcoming' => [
        '#type' => 'details',
        '#title' => t('Upcoming invoice'),
        '#open' => 'TRUE',
        '#description' => !empty($upcoming_invoice) ? t('This invoice is subject to change.') : NULL,
        'empty' => [
          '#type' => 'item',
          '#markup' => t('There is currently no invoice to display.'),
          '#access' => empty($upcoming_invoice),
        ],
        'invoice' => [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => [$upcoming_invoice ? $this->formatInvoiceTableRow($upcoming_invoice) : []],
          '#access' => !empty($upcoming_invoice),
        ],
      ],
      'invoices' => [
        '#type' => 'details',
        '#title' => t('Past invoices'),
        '#open' => 'TRUE',
        'empty' => [
          '#type' => 'item',
          '#markup' => t('There are currently no invoices to display.'),
          '#access' => empty($invoices),
        ],
        'list' => [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => [],
          '#access' => !empty($invoices),
        ],
      ],
      'notice' => [
        '#type' => 'item',
        '#markup' => t('Invoices are updated approximately every 24 hours.'),
      ],
      '#cache' => [
        'keys' => ['invoices'],
        'contexts' => [
          'user',
        ],
        'tags' => array_merge(
          $this->beaconBilling->getUserSubscription()->getCacheTags(),
          ['user.channels:' . $this->beaconBilling->getUser()->id()]
        ),
        'max-age' => BeaconBilling::CACHE_LIFETIME,
      ],
    ];

    // Add rows for each invoice.
    foreach ($invoices as $invoice) {
      $build['invoices']['list']['#rows'][] = $this->formatInvoiceTableRow($invoice);
    }

    return $build;
  }

  /**
   * Format a invoice for a table row.
   *
   * @param \Stripe\Invoice $invoice
   *   A Stripe invoice.
   *
   * @return array
   *   An array to be used in an invoice table.
   */
  public function formatInvoiceTableRow(Invoice $invoice) {
    return [
      Link::fromTextAndUrl($invoice->number, Url::fromRoute('beacon_billing.invoice', ['invoice_number' => $invoice->number])),
      format_date($invoice->date, 'custom', 'F j, Y'),
      format_date($invoice->lines->data[0]->period->start),
      format_date($invoice->lines->data[0]->period->end),
      $this->formatInvoicePrice($invoice->total),
      $invoice->paid ? t('Yes') : t('No'),
    ];
  }

  /**
   * Format the price of an invoice.
   *
   * @param int $price
   *   An invoice price.
   *
   * @return string
   *   The invoice price formatted..
   */
  public function formatInvoicePrice($price) {
    return '$' . number_format($price / 100, 2);
  }

  /**
   * Render a single invoice.
   *
   * @param string $invoice_number
   *   An invoice name.
   *
   * @return array
   *   A renderable array.
   */
  public function invoice(string $invoice_number) {
    // Fetch the invoices.
    $invoices = $this->beaconBilling->getInvoices();

    // Check if the invoice call failed.
    if ($invoices === FALSE) {
      drupal_set_message(t('An error occurred while loading your invoice. Please try again or contact support for assistance.'), 'error');
      return new RedirectResponse(Url::fromRoute('beacon_billing.invoices')->toString());
    }

    // Iterate the invoices and try to find a match.
    $match = FALSE;
    foreach ($invoices as $invoice) {
      if ($invoice->number == $invoice_number) {
        $match = TRUE;
        break;
      }
    }

    // Check if there was no match.
    if (!$match) {
      // Load the upcoming invoice.
      $invoice = $this->beaconBilling->getUpcomingInvoice();

      // Check if there is not a match.
      if (!$invoice || ($invoice->number != $invoice_number)) {
        // Redirect back to the list.
        drupal_set_message(t('The requested invoice was not found.'), 'warning');
        return new RedirectResponse(Url::fromRoute('beacon_billing.invoices')->toString());
      }
    }

    // Build out the invoice.
    $build = [
      '#theme' => 'invoice',
      '#site_name' => $this->configFactory->get('system.site')->get('name'),
      '#date' => format_date($invoice->date, 'custom', 'F j, Y'),
      '#number' => $invoice->number,
      '#discounted' => !empty($invoice->discount),
      '#discount_percent' => !empty($invoice->discount) ? $invoice->discount->coupon->percent_off : NULL,
      '#discount_amount_off' => !empty($invoice->discount) ? $invoice->discount->coupon->amount_off : NULL,
      '#discount_total' => 0,
      '#tax' => $invoice->tax ? $this->formatInvoicePrice($invoice->tax) : NULL,
      '#tax_percent' => $invoice->tax_percent,
      '#subtotal' => $this->formatInvoicePrice($invoice->subtotal),
      '#total' => $this->formatInvoicePrice($invoice->total),
      '#paid' => $invoice->paid,
      '#period_start' => format_date($invoice->lines->data[0]->period->start),
      '#period_end' => format_date($invoice->lines->data[0]->period->end),
      '#invoices_url' => Url::fromRoute('beacon_billing.invoices'),
      '#lines' => [
        '#theme' => 'table',
        '#header' => [
          t('Quantity'),
          t('Description'),
          t('Total'),
        ],
        '#rows' => [],
      ],
      '#cache' => [
        'keys' => ['invoice:' . $invoice->number],
        'max-age' => BeaconBilling::CACHE_LIFETIME,
        'tags' => array_merge(
          $this->beaconBilling->getUserSubscription()->getCacheTags(),
          ['user.channels:' . $this->beaconBilling->getUser()->id()]
        ),
        'contexts' => [
          'url.path',
          'user',
        ],
      ],
    ];

    // Check if there is a discount.
    if ($build['#discounted']) {
      // Check if the discount is a fixed-amount.
      if ($build['#discount_amount_off']) {
        // This is the total discount.
        $build['#discount_total'] = $build['#discount_amount_off'];
      }
      elseif ($build['#discount_percent']) {
        // Determine the discountable total.
        $discountable_total = 0;
        foreach ($invoice->lines->data as $line) {
          if ($line->discountable) {
            $discountable_total += $line->amount;
          }
        }

        // Calculate the discount.
        $build['#discount_total'] = '-' . $this->formatInvoicePrice(round(($discountable_total / 100) * ($build['#discount_percent']), 2));
      }
    }

    // Add the line rows.
    foreach ($invoice->lines->data as $line) {
      $build['#lines']['#rows'][] = [
        $line->quantity,
        $line->description,
        $this->formatInvoicePrice($line->amount),
      ];
    }

    return $build;
  }

}
