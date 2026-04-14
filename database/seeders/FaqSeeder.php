<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'     => 'Registration',
                'slug'     => 'registration',
                'order'    => 1,
                'is_active' => true,
                'faqs'     => [
                    [
                        'question' => 'Paano mag-transfer ng funds from BINANCE to Riscoin?',
                        'answer'   => '<p><strong>Sagot:</strong></p>
<ol>
<li>Mag-login sa Binance</li>
<li>Pumunta sa <strong>Wallet &gt; Withdraw</strong></li>
<li>Piliin ang <strong>USDT</strong> (o coin na supported ng Riscoin)</li>
<li>I-paste ang Riscoin deposit address</li>
<li>Piliin ang tamang network (<strong>TRC20 / ERC20 / BEP20</strong> depende sa Riscoin)</li>
<li>I-confirm ang transaction</li>
</ol>
<p>⚠️ <strong>Paalala:</strong> Siguraduhing tama ang network para maiwasan ang loss of funds.</p>',
                    ],
                    [
                        'question' => 'Paano mag-transfer ng funds from OKX to Riscoin?',
                        'answer'   => '<p><strong>Sagot:</strong></p>
<ol>
<li>Login sa OKX</li>
<li>Go to <strong>Assets &gt; Withdraw</strong></li>
<li>Piliin ang USDT</li>
<li>Ilagay ang Riscoin wallet address</li>
<li>Piliin ang tamang network</li>
<li>Confirm withdrawal</li>
</ol>',
                    ],
                    [
                        'question' => 'Paano mag-transfer ng funds from Bitget to Riscoin?',
                        'answer'   => '<p><strong>Sagot:</strong></p>
<ol>
<li>Buksan ang Bitget account</li>
<li>Pumunta sa <strong>Withdraw section</strong></li>
<li>Piliin ang crypto asset (USDT)</li>
<li>I-input ang Riscoin deposit address</li>
<li>Piliin ang correct network</li>
<li>I-confirm ang transaction</li>
</ol>',
                    ],
                    [
                        'question' => 'Saan makikita ang personal Riscoin link?',
                        'answer'   => '<p><strong>Sagot:</strong><br>Makikita ito sa iyong <strong>Riscoin Dashboard &gt; Profile / Referral Section</strong>. Doon mo makikita ang iyong unique referral link.</p>',
                    ],
                    [
                        'question' => 'Saan makikita ang personal Portal link?',
                        'answer'   => '<p><strong>Sagot:</strong><br>Pumunta sa <strong>Account Settings / Dashboard</strong>, makikita ang Portal link sa Account Overview o welcome page.</p>',
                    ],
                    [
                        'question' => 'Paano i-setup ang Google Authenticator?',
                        'answer'   => '<p><strong>Sagot:</strong></p>
<ol>
<li>I-download ang <strong>Google Authenticator</strong> app</li>
<li>Pumunta sa Riscoin <strong>Security Settings</strong></li>
<li>Piliin ang <strong>Enable 2FA</strong></li>
<li>I-scan ang QR code</li>
<li>Ilagay ang verification code para ma-activate</li>
</ol>',
                    ],
                    [
                        'question' => 'Saan makikita ang Riscoin ID?',
                        'answer'   => '<p><strong>Sagot:</strong><br>Makikita ito sa <strong>Profile section</strong> ng iyong account dashboard bilang iyong unique user ID.</p>',
                    ],
                    [
                        'question' => 'Saan makikita ang Bonchat ID?',
                        'answer'   => '<p><strong>Sagot:</strong><br>Makikita ito sa <strong>Bonchat profile page</strong> o sa settings ng account mo sa app.</p>',
                    ],
                ],
            ],
            [
                'name'     => 'Buy Crypto / USDT via P2P',
                'slug'     => 'buy-crypto-usdt-via-p2p',
                'order'    => 2,
                'is_active' => true,
                'faqs'     => [
                    [
                        'question' => 'Paano bumili ng USDT sa Binance P2P?',
                        'answer'   => '<p><strong>Sagot:</strong></p>
<ol>
<li>Pumunta sa <strong>Binance App</strong></li>
<li>Click <strong>P2P Trading</strong></li>
<li>Piliin ang <strong>Buy &gt; USDT</strong></li>
<li>Piliin ang seller at payment method</li>
<li>Magbayad sa seller</li>
<li>I-click ang <strong>Paid</strong> at hintayin ang release ng USDT</li>
</ol>',
                    ],
                    [
                        'question' => 'Paano bumili ng USDT sa OKX P2P?',
                        'answer'   => '<p><strong>Sagot:</strong></p>
<ol>
<li>Login sa OKX</li>
<li>Pumunta sa <strong>P2P section</strong></li>
<li>Piliin ang <strong>Buy USDT</strong></li>
<li>Pumili ng seller</li>
<li>Bayaran ang seller gamit ang payment method</li>
<li>Hintayin ang crypto release</li>
</ol>',
                    ],
                    [
                        'question' => 'Paano bumili ng USDT sa Bitget P2P?',
                        'answer'   => '<p><strong>Sagot:</strong></p>
<ol>
<li>Open Bitget app</li>
<li>Go to <strong>P2P Trading</strong></li>
<li>Select USDT</li>
<li>Piliin ang seller</li>
<li>Magbayad base sa instructions</li>
<li>I-confirm payment at hintayin release</li>
</ol>',
                    ],
                ],
            ],
            [
                'name'     => 'Common Questions',
                'slug'     => 'common-questions',
                'order'    => 3,
                'is_active' => true,
                'faqs'     => [
                    [
                        'question' => 'Gaano katagal bago ma-receive ang transfer?',
                        'answer'   => '<p><strong>Sagot:</strong><br>Karaniwan <strong>1–10 minutes</strong> depende sa network congestion.</p>',
                    ],
                    [
                        'question' => 'Ano ang gagawin kapag mali ang network na napili?',
                        'answer'   => '<p><strong>Sagot:</strong><br>Kapag mali ang network, posibleng <strong>hindi na ma-recover ang funds</strong>. Makipag-ugnayan agad sa support.</p>',
                    ],
                    [
                        'question' => 'Safe ba ang P2P trading?',
                        'answer'   => '<p><strong>Sagot:</strong><br>Oo, basta gumamit ng <strong>verified sellers</strong> at huwag mag-release ng payment confirmation hangga\'t di pa bayad.</p>',
                    ],
                    [
                        'question' => 'Ano ang dapat gawin kapag hindi dumating ang USDT?',
                        'answer'   => '<p><strong>Sagot:</strong><br>I-check ang <strong>transaction hash (TXID)</strong> at makipag-ugnayan sa support ng platform.</p>',
                    ],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $faqs = $catData['faqs'];
            unset($catData['faqs']);

            $category = FaqCategory::firstOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );

            $order = 1;
            foreach ($faqs as $faqData) {
                Faq::firstOrCreate(
                    [
                        'faq_category_id' => $category->id,
                        'question'        => $faqData['question'],
                    ],
                    [
                        'answer'  => $faqData['answer'],
                        'status'  => 'published',
                        'order'   => $order++,
                    ]
                );
            }
        }
    }
}
