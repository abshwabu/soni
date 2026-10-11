<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Soni Perfume',
                'slug' => 'about',
                'content' => <<<HTML
<h2>The Soni Perfume Legacy</h2>
<p>Founded in the vibrant heart of Addis Ababa, <strong>Soni Perfume</strong> is Ethiopia’s premier boutique destination for genuine international designer fragrances, bespoke Arabian ouds, and niche artisanal extraits.</p>

<h3>Our Philosophy: Authenticity Without Compromise</h3>
<p>In a fragrance landscape frequently clouded by replicas and diluted oils, Soni Perfume was born out of an uncompromising commitment: <strong>every single bottle on our shelves is 100% original, sealed, and traceable directly to authorized European and Gulf distributors</strong>.</p>

<h3>The Addis Ababa Experience</h3>
<p>From our flagship showroom located in Bole Medhanialem to our direct-to-door express delivery service across Addis Ababa, we treat fragrance shopping as a deeply personal sensory journey. Whether you are searching for a signature daily scent, a regal oriental oud for grand celebrations, or a treasured gift for someone special, our dedicated perfume consultants guide you through top notes, sillage, and scent chemistry.</p>

<h3>Our Core Values</h3>
<ul>
    <li><strong>Uncompromising Authenticity:</strong> Zero counterfeit policy, sealed manufacturer packaging, and batch code transparency.</li>
    <li><strong>Local Express Fulfillment:</strong> Same-day dispatch within Addis Ababa with optional pay-on-delivery inspection.</li>
    <li><strong>Personalized Concierge:</strong> Direct customer guidance via Telegram and WhatsApp with complimentary fragrance consultations.</li>
</ul>
HTML,
                'meta_title' => 'About Soni Perfume | Luxury Fragrance Boutique Addis Ababa',
                'meta_description' => 'Discover the story, authenticity guarantee, and artisan heritage behind Soni Perfume, Addis Ababa’s premier fragrance boutique.',
                'is_published' => true,
            ],
            [
                'title' => 'Delivery & Returns Policy',
                'slug' => 'delivery-returns',
                'content' => <<<HTML
<h2>Fast, Secure Delivery Across Addis Ababa</h2>
<p>At Soni Perfume, we understand that when you order a luxury fragrance, you want it handled with care and delivered swiftly.</p>

<h3>Delivery Options & Timelines</h3>
<ul>
    <li><strong>Express Addis Ababa Delivery:</strong> Orders confirmed before 4:00 PM are delivered same-day. Orders placed later are delivered the following morning.</li>
    <li><strong>Standard Delivery Fee:</strong> Flat fee of ETB 200 within Addis Ababa city limits.</li>
    <li><strong>Free Delivery Privilege:</strong> Complimentary delivery on all orders of <strong>ETB 3,000 or more</strong>.</li>
    <li><strong>In-Store Boutique Pickup:</strong> Free instant collection from our Bole Medhanialem boutique during opening hours (Mon–Sat 9am–8pm, Sun 10am–6pm).</li>
</ul>

<h3>Inspection on Delivery</h3>
<p>For your complete peace of mind, we invite you to inspect the exterior packaging and security seals upon delivery before finalizing payment with our courier.</p>

<h3>Returns & Exchange Policy</h3>
<p>Due to the personal and cosmetic nature of authentic luxury fragrances:</p>
<ul>
    <li>Unopened, sealed items in original protective cellophane packaging may be exchanged within <strong>48 hours</strong> of delivery.</li>
    <li>Opened or unsealed perfume bottles cannot be returned or exchanged to safeguard product authenticity and hygiene for all customers.</li>
    <li>In the unlikely event of transit damage, notify our concierge within 24 hours via Telegram or WhatsApp (+251 91 100 0000) for an immediate replacement.</li>
</ul>
HTML,
                'meta_title' => 'Delivery & Returns | Soni Perfume Addis Ababa',
                'meta_description' => 'Same-day express delivery across Addis Ababa, free delivery thresholds, and return policy for Soni Perfume orders.',
                'is_published' => true,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'content' => <<<HTML
<h2>Your Privacy is Our Priority</h2>
<p>Soni Perfume operates as a guest-first boutique. We believe in minimal data footprint and maximum confidentiality for our valued clients in Ethiopia and abroad.</p>

<h3>Information We Collect</h3>
<p>When you place an order or contact us, we collect only the essential details necessary to fulfill your delivery:</p>
<ul>
    <li>Your full name for delivery identification.</li>
    <li>Phone number (and optional alternate phone) for delivery driver coordination and Telegram order status notifications.</li>
    <li>Delivery address and local landmarks in Addis Ababa.</li>
    <li>Optional scent preference notes and gift recipient instructions.</li>
</ul>

<h3>No Online Payment or Card Storage</h3>
<p>Because Soni Perfume operates on a cash/mobile-transfer on delivery and store-pickup model, <strong>we do NOT collect, process, or store credit card numbers, CVVs, or bank login credentials</strong> on our servers.</p>

<h3>Order Notifications via Telegram</h3>
<p>Order information is forwarded securely to our admin dispatch bot on Telegram to ensure instant packaging and dispatch. Your information is never sold, leased, or distributed to third-party marketing companies.</p>

<h3>Contact Us</h3>
<p>If you have any questions or would like your contact history removed from our records, please reach out to our team at <a href="mailto:privacy@soniperfume.com">privacy@soniperfume.com</a> or via our Telegram concierge.</p>
HTML,
                'meta_title' => 'Privacy Policy | Soni Perfume',
                'meta_description' => 'Learn how Soni Perfume protects client confidentiality, guest checkouts, and customer phone numbers.',
                'is_published' => true,
            ],
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms',
                'content' => <<<HTML
<h2>Terms & Conditions of Service</h2>
<p>Welcome to Soni Perfume. By visiting our website or placing an order with us, you agree to the following terms and operating practices.</p>

<h3>1. Authenticity Guarantee</h3>
<p>Soni Perfume guarantees that all perfumes, colognes, attars, and body mists sold are 100% authentic, brand-new, and sealed in original manufacturer packaging.</p>

<h3>2. Pricing & Currency</h3>
<p>All prices displayed on the storefront are in <strong>whole Ethiopian Birr (ETB)</strong>. Prices are inclusive of all applicable local import tariffs and taxes. Soni Perfume reserves the right to adjust pricing based on international currency fluctuations without prior notice.</p>

<h3>3. Order Confirmation & Fulfillment</h3>
<p>Submitting an order online represents an order request. Because inventory moves quickly in our boutique, our dispatch team verifies physical stock and reaches out via phone or Telegram before the courier leaves our showroom.</p>

<h3>4. Payment Terms</h3>
<p>Payment is due upon receipt of goods via Cash on Delivery or Telebirr/CBE Birr bank transfer directly to the authorized store merchant account.</p>

<h3>5. Governing Law</h3>
<p>These terms and any transactions conducted through Soni Perfume are governed by the commercial laws of the Federal Democratic Republic of Ethiopia.</p>
HTML,
                'meta_title' => 'Terms & Conditions | Soni Perfume',
                'meta_description' => 'Terms of service, authenticity warranty, and order fulfillment policies for Soni Perfume.',
                'is_published' => true,
            ],
        ];

        foreach ($pages as $p) {
            Page::updateOrCreate(['slug' => $p['slug']], $p);
        }
    }
}
