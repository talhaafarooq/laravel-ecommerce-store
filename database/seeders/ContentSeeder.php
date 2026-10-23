<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Post;
use App\Models\User;
use App\Services\ImageService;
use Database\Seeders\Support\RemoteImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Blog posts, FAQs and a few contact messages.
 */
final class ContentSeeder extends Seeder
{
    public function __construct(
        private readonly RemoteImages $remote,
        private readonly ImageService $images,
    ) {}

    public function run(): void
    {
        $this->seedPosts();
        $this->seedFaqs();
        $this->seedMessages();
    }

    private function seedPosts(): void
    {
        $author = User::query()->where('email', UserSeeder::ADMIN_EMAIL)->firstOrFail();
        $posts  = $this->posts();
        $covers = $this->remote->fetchMany(array_map(
            static fn (array $post): string => 'https://picsum.photos/seed/' . Str::slug($post['title']) . '/1600/900',
            $posts,
        ));

        foreach ($posts as $index => $post) {
            Post::query()->create([
                'author_id'        => $author->id,
                'title'            => $post['title'],
                'slug'             => Str::slug($post['title']),
                'excerpt'          => $post['excerpt'],
                'body'             => $post['body'],
                'cover_image_path' => $this->images->store($covers[$index], 'posts', 1600, 900),
                'published_at'     => $index === 0 ? null : now()->subDays($index * 9)->setTime(9, 0),
                'meta_description' => $post['excerpt'],
            ]);
        }
    }

    private function seedFaqs(): void
    {
        $position = 0;

        foreach ($this->faqs() as $topic => $items) {
            foreach ($items as [$question, $answer]) {
                Faq::query()->create([
                    'topic'        => $topic,
                    'question'     => $question,
                    'answer'       => $answer,
                    'position'     => $position++,
                    'is_published' => true,
                ]);
            }
        }
    }

    private function seedMessages(): void
    {
        ContactMessage::factory(6)->create();
        ContactMessage::factory(4)->read()->create();
    }

    /**
     * @return list<array{title: string, excerpt: string, body: string}>
     */
    private function posts(): array
    {
        return [
            [
                'title'   => 'Holiday Gift Guide: Ideas for Everyone on Your List',
                'excerpt' => 'Our team picked the gifts they would love to unwrap, from tech upgrades to cosy home touches.',
                'body'    => <<<'MD'
                    The best gifts are useful, personal and a little bit unexpected. We asked the team for their favourites — this draft is scheduled for the season.

                    ## For the tech lover

                    - **Wireless earbuds** that disappear in your ears.
                    - A **compact tablet** for reading and streaming on the go.

                    ## For the home cook

                    A sharp knife and a sturdy cutting board will be used every single day.
                    MD,
            ],
            [
                'title'   => 'How to Choose the Right Laptop for Work and Study',
                'excerpt' => 'Processor, memory, storage, screen: a jargon-free guide to picking a laptop you will be happy with for years.',
                'body'    => <<<'MD'
                    Buying a laptop can feel overwhelming. Spec sheets are long and every model claims to be the fastest. Here is what actually matters.

                    ## Start with how you work

                    If you mostly write, browse and join video calls, almost any modern processor will do. Creative work such as photo editing or programming benefits from more cores and more memory.

                    ## Memory and storage

                    - **16 GB of RAM** is the sweet spot for multitasking in 2026.
                    - Choose an **SSD of at least 512 GB**; cloud storage is great, but local space fills up fast.

                    ## Screen and battery

                    You will look at the screen for hours, so prioritise brightness and a comfortable size. A battery that lasts a full working day removes the need to hunt for outlets.

                    > Tip: if you travel often, weight matters more than you think. Anything under 1.4 kg is easy to carry all day.

                    Browse our [laptops](/categories/laptops) to compare models side by side.
                    MD,
            ],
            [
                'title'   => '5 Skin Care Habits Dermatologists Swear By',
                'excerpt' => 'Healthy skin is more about consistency than expensive products. These five habits make the biggest difference.',
                'body'    => <<<'MD'
                    Great skin rarely comes from a single miracle product. It comes from simple habits repeated every day.

                    1. **Wear sunscreen daily** — even when it is cloudy. UV exposure is the leading cause of premature ageing.
                    2. **Cleanse gently.** Harsh scrubs damage the skin barrier; a mild cleanser is enough.
                    3. **Moisturise while skin is damp** to lock in hydration.
                    4. **Introduce actives slowly.** Retinoids and acids work best when your skin has time to adjust.
                    5. **Sleep and hydrate.** Your skin repairs itself overnight.

                    Find gentle cleansers, serums and SPF in our [skin care](/categories/skin-care) collection.
                    MD,
            ],
            [
                'title'   => 'Small Space, Big Style: Decorating Tips for Apartments',
                'excerpt' => 'Mirrors, multi-purpose furniture and smart lighting can make a compact home feel open and inviting.',
                'body'    => <<<'MD'
                    A small home can feel just as welcoming as a large one. The trick is to make every piece earn its place.

                    ## Use light to your advantage

                    Mirrors bounce daylight around the room, and layered lamps make evenings feel warm instead of dim.

                    ## Choose furniture that does two jobs

                    A storage ottoman, an extendable table or a bed with drawers frees up valuable floor space.

                    ## Keep a consistent palette

                    Two or three colours repeated across textiles and decor make a space feel calm and cohesive.

                    Explore [home decor](/categories/home-decor) and [furniture](/categories/furniture) picks curated for compact living.
                    MD,
            ],
            [
                'title'   => 'The Beginner\'s Guide to Building a Capsule Wardrobe',
                'excerpt' => 'Fewer, better pieces that work together. Here is how to build a wardrobe that makes getting dressed effortless.',
                'body'    => <<<'MD'
                    A capsule wardrobe is a small collection of versatile clothes that can be mixed and matched. It saves time, money and closet space.

                    ## Pick a base palette

                    Neutrals such as navy, grey, white and camel pair with almost everything. Add one or two accent colours you love.

                    ## Invest in the essentials

                    - A well-fitting pair of jeans
                    - Crisp shirts and simple tops
                    - Comfortable everyday shoes
                    - One versatile jacket

                    ## Buy for quality

                    It is better to own five pieces you wear constantly than twenty you rarely reach for.
                    MD,
            ],
            [
                'title'   => 'Meal Prep 101: Save Time and Eat Better All Week',
                'excerpt' => 'An hour of preparation on Sunday can mean healthy, home-cooked meals every day. Here is a simple plan to get started.',
                'body'    => <<<'MD'
                    Meal prepping does not have to mean eating the same sad lunch five days in a row.

                    ## Cook components, not meals

                    Roast a tray of vegetables, cook a pot of grains and prepare one or two proteins. Combine them differently each day.

                    ## Get the right containers

                    Glass containers keep food fresh and go straight from the fridge to the microwave.

                    ## Keep a well-stocked pantry

                    Spices, sauces and staples turn simple ingredients into exciting dishes. Our [kitchen](/categories/kitchen) and [groceries](/categories/fresh-pantry) sections have you covered.
                    MD,
            ],
            [
                'title'   => 'Why a Good Watch Is Still Worth It in the Smartphone Era',
                'excerpt' => 'A watch is more than a way to tell time. It is craftsmanship, style and a piece you can pass down.',
                'body'    => <<<'MD'
                    Phones tell time perfectly, so why do watches remain so popular? Because a great watch is about more than function.

                    ## Craftsmanship you can feel

                    From sapphire crystals to precise movements, quality watches are engineered to last decades.

                    ## A signature accessory

                    The right watch completes an outfit, whether you wear a suit or a T-shirt.

                    ## Fewer distractions

                    Checking the time on a watch does not pull you into notifications.

                    Discover [men's](/categories/men-s-watches) and [women's](/categories/women-s-watches) watches in our collection.
                    MD,
            ],
            [
                'title'   => 'Stay Active at Home: Equipment Worth Owning',
                'excerpt' => 'You do not need a gym membership to stay fit. A few well-chosen pieces of equipment go a long way.',
                'body'    => <<<'MD'
                    Building a home workout routine is easier than ever. Start with versatile equipment that fits in a corner.

                    - **Resistance bands** for strength training anywhere.
                    - **A quality mat** for yoga, stretching and core work.
                    - **Adjustable dumbbells** that replace an entire rack.
                    - **A jump rope** for quick, effective cardio.

                    Consistency beats intensity: twenty minutes a day adds up quickly. Shop [sports equipment](/categories/sports-equipment) to get started.
                    MD,
            ],
        ];
    }

    /**
     * @return array<string, list<array{0: string, 1: string}>>
     */
    private function faqs(): array
    {
        return [
            'Orders' => [
                ['How do I track my order?', 'Sign in and open **My orders**. Every order shows its current status and a full timeline, and we email you whenever the status changes.'],
                ['Can I cancel my order?', 'Yes — orders can be cancelled from the order page while they are still pending and unpaid. Once we start processing an order, please contact us and we will do our best to help.'],
                ['Can I change my shipping address after ordering?', 'Contact us as soon as possible. If the order has not shipped yet we can update the address for you.'],
                ['Where can I find my invoice?', 'Every order page has a **Download invoice** button that gives you a PDF copy for your records.'],
            ],
            'Payments' => [
                ['Which payment methods do you accept?', 'You can pay securely online by card through Stripe, or choose **Cash on Delivery** and pay the courier when your parcel arrives.'],
                ['Is it safe to pay by card?', 'Yes. Card payments are processed by Stripe on a secure, PCI-compliant page. We never see or store your card number.'],
                ['My payment failed. What should I do?', 'Your order is kept for a short time. Open the order and choose **Pay now** to try again, or contact your bank if the problem continues.'],
                ['How do discount codes work?', 'Enter your code in the cart. Some codes have a minimum order value, an expiry date or can be used once per customer.'],
            ],
            'Shipping' => [
                ['How much does shipping cost?', 'Standard shipping is a flat rate, and orders above the free-shipping threshold ship for free. The exact amount is shown in your cart before you check out.'],
                ['How long does delivery take?', 'Most orders are dispatched within one business day and arrive within 3–5 business days.'],
                ['Do you ship internationally?', 'We currently ship to a growing list of countries. Enter your address at checkout to see whether we deliver to you.'],
            ],
            'Returns' => [
                ['What is your return policy?', 'Most items can be returned within 30 days of delivery in their original condition. Some products, such as groceries and personal care items, cannot be returned for hygiene reasons.'],
                ['How do I start a return?', 'Send us a message through the contact page with your order number and we will email you a prepaid return label.'],
                ['When will I get my refund?', 'Refunds are issued to your original payment method within 5 business days of us receiving the item.'],
            ],
            'Account' => [
                ['Do I need an account to shop?', 'You can browse and fill your cart as a guest. An account is required to check out so you can track orders and download invoices.'],
                ['How do I reset my password?', 'Choose **Forgot password** on the sign-in page and we will email you a secure reset link.'],
                ['How can I protect my account?', 'Turn on two-factor authentication or add a passkey under **Settings → Security** for an extra layer of protection.'],
            ],
        ];
    }
}
