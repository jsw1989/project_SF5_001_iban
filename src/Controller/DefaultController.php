<?php

namespace App\Controller;

use App\Service\APICaller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class DefaultController extends AbstractController
{
    public $urls = [
        'wallets' => 'https://sandbox2.ibanfirst.com/api/wallets/',
        'movements' => 'https://sandbox2.ibanfirst.com/api/financialMovements/',
        'currency' => 'https://api.exchangeratesapi.io/latest'
    ];

    /**
     * @Route("/", name="default")
     */
    public function index(APICaller $apicaller)
    {
        // lauch api caller for currency rate url
        $jsonCurrencies = $apicaller->launchCall($this->urls['currency']);
        // launch call for API via APICaller Service with url of wallets
        $jsonWallets = $apicaller->launchCall($this->urls['wallets']);
        $totalWallets = count($jsonWallets['wallets']);
        $totalBookingAmount = $totalTransactionsAmount = 0;
        foreach ($jsonWallets['wallets'] as $wallet) {
            $wcurrency = $wallet['bookingAmount']['currency'];
            // convert all amount to euro
            $wAmount = ($wcurrency != 'EUR') ? $wallet['bookingAmount']['value'] * $jsonCurrencies['rates'][$wcurrency] : $wallet['bookingAmount']['value'];
            $totalBookingAmount += $wAmount;
        }
        // lauch call for API via APIcaller Service with url of financialMovements
        $jsonMovements = $apicaller->launchCall($this->urls['movements']);
        $singleWallets = [];
        foreach ($jsonMovements['financialMovements'] as $movement) {
            // store only wallets with financial movements
            $singleWallets[$movement['walletId']] = $movement['walletId'];
            // convert all amount to euro
            $fAmount = ($movement['amount']['currency'] != 'EUR') ? $movement['amount']['value'] * $jsonCurrencies['rates'][$movement['amount']['currency']] : $movement['amount']['value'];
            $totalTransactionsAmount += $fAmount;
        }
        $withMovements = count($singleWallets);

        return $this->render('default/index.html.twig',
            [
                'totalWallets' => $totalWallets,
                'withoutMovements' => $totalWallets - $withMovements,
                'withMovements' => $withMovements,
                'totalBookingAmount' => $totalBookingAmount,
                'totalTransactionsAmount' => $totalTransactionsAmount,
            ]

        );
    }

    /**
     * @Route("/wallets", name="wallets_index")
     */
    public function walletIndex(APICaller $apicaller)
    {
        // launch call for API via APICaller Service with url of wallets
        $jsonWallets = $apicaller->launchCall($this->urls['wallets']);
        // lauch call for API via APIcaller Service with url of financialMovements
        // this call is used to check which wallets have f. movements
        $jsonMovements = $apicaller->launchCall($this->urls['movements']);
        foreach ($jsonMovements['financialMovements'] as $movement) {
            $singleWallets[$movement['walletId']] = $movement['walletId'];
        }
        $wallets = [];
        foreach ($jsonWallets['wallets'] as $index => $wallet) {
            $wallets[$index] = $wallet;
            $wallets[$index]['hasTransactions'] = isset($singleWallets[$wallet['id']]) ? true : false;

        }
        unset($singleWallets);
        return $this->render('default/wallets.html.twig', [
            'wallets' => $wallets,
        ]);
    }

    /**
     * @Route("/movements", name="movements_index")
     */
    public function movementIndex($wallet_id, APICaller $apicaller)
    {
        // launch call for API via APICaller Service with Url of financialMovements
        $jsonMovements = $apicaller->launchCall($this->urls['movements']);
        $movements = [];
        foreach ($jsonMovements['financialMovements'] as $movement) {
            // filter movemnts via wallet id . full is a specifc wallet which has the role to retreive all financial movements
            if ($wallet_id != 'full' and $movement['walletId'] != $wallet_id) {
                continue;
            }
            $movements[] = $movement;
        }
        // render movements twig template with twoo arguments movements and linkBack
        // linkBack is a link which be shown when user click on a f. movements from wallets interface
        return $this->render('default/movements.html.twig', [
            'movements' => $movements,
            'linkBack' => $wallet_id != 'full' ? 1 : 0
        ]);

    }
}
