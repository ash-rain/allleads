<?php

namespace Database\Seeders;

use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoLeadSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();

        $batch = ImportBatch::create([
            'uuid' => Str::uuid(),
            'filename' => 'demo-leads.csv',
            'status' => 'completed',
            'progress' => 100,
            'total' => 20,
            'created_count' => 20,
            'created_by' => $admin->id,
        ]);

        $webDevTag = Tag::where('slug', 'web-dev-prospect')->first();
        $highRatingTag = Tag::where('slug', 'high-rating')->first();
        $noWebsiteTag = Tag::where('slug', 'no-website')->first();

        $leads = [
            ['title' => 'Пекарна Слънчева', 'category' => 'Bakery', 'address' => 'ул. Витоша 12, София', 'phone' => '+359887123456', 'website' => null, 'email' => null, 'review_rating' => 4.8],
            ['title' => 'Автосервиз Иванов', 'category' => 'Auto Repair', 'address' => 'бул. Черни Връх 45, София', 'phone' => '+359888654321', 'website' => null, 'email' => null, 'review_rating' => 4.7],
            ['title' => 'Ресторант Дракон', 'category' => 'Restaurant', 'address' => 'ул. Граф Игнатиев 8, София', 'phone' => '+359889111222', 'website' => null, 'email' => null, 'review_rating' => 4.9],
            ['title' => 'Зъболекарски кабинет Стоянова', 'category' => 'Dentist', 'address' => 'ул. Сердика 3, Пловдив', 'phone' => '+359878333444', 'website' => null, 'email' => null, 'review_rating' => 4.6],
            ['title' => 'Фризьорски салон Мода', 'category' => 'Hair Salon', 'address' => 'бул. Александър Стамболийски 22, Пловдив', 'phone' => '+359877555666', 'website' => null, 'email' => null, 'review_rating' => 4.8],
            ['title' => 'Книжарница Светлина', 'category' => 'Bookstore', 'address' => 'ул. Батенберг 14, Варна', 'phone' => '+359866777888', 'website' => 'https://svetlina-books.bg', 'email' => 'info@svetlina-books.bg', 'review_rating' => 4.5],
            ['title' => 'Аптека Здраве', 'category' => 'Pharmacy', 'address' => 'пл. Независимост 1, Варна', 'phone' => '+359865999000', 'website' => null, 'email' => null, 'review_rating' => 4.7],
            ['title' => 'Хотел Черноморец', 'category' => 'Hotel', 'address' => 'ул. Крайбрежна 5, Бургас', 'phone' => '+359856111333', 'website' => null, 'email' => null, 'review_rating' => 4.6],
            ['title' => 'Кафе Арт', 'category' => 'Cafe', 'address' => 'ул. Патриарх Евтимий 7, Стара Загора', 'phone' => '+359845444555', 'website' => null, 'email' => null, 'review_rating' => 5.0],
            ['title' => 'Мебели Вие', 'category' => 'Furniture', 'address' => 'Индустриална зона, Пазарджик', 'phone' => '+359834666777', 'website' => null, 'email' => null, 'review_rating' => 4.9],
            ['title' => 'Строителна фирма Здрава Основа', 'category' => 'Construction', 'address' => 'ул. Братя Миладинови 30, Русе', 'phone' => '+359823888999', 'website' => null, 'email' => null, 'review_rating' => 4.8],
            ['title' => 'Магазин за цветя Роза', 'category' => 'Florist', 'address' => 'ул. Дунав 11, Русе', 'phone' => '+359812000111', 'website' => null, 'email' => null, 'review_rating' => 4.7],
            ['title' => 'Клуб по бокс Шампион', 'category' => 'Sports Club', 'address' => 'ул. Олимпийска 2, Плевен', 'phone' => '+359801222333', 'website' => null, 'email' => null, 'review_rating' => 4.9],
            ['title' => 'Козметичен салон Клеопатра', 'category' => 'Beauty Salon', 'address' => 'бул. Тракия 55, Пловдив', 'phone' => '+359890444555', 'website' => null, 'email' => null, 'review_rating' => 4.8],
            ['title' => 'Магазин за спорт Победа', 'category' => 'Sports Store', 'address' => 'мол Марково Тера, Пловдив', 'phone' => '+359989666777', 'website' => null, 'email' => null, 'review_rating' => 4.6],
            ['title' => 'Правна кантора Иванов и Партньори', 'category' => 'Law Firm', 'address' => 'ул. Г.С. Раковски 117, София', 'phone' => '+359988888999', 'website' => null, 'email' => 'office@ivanov-law.bg', 'review_rating' => 4.8],
            ['title' => 'Рибарница Нептун', 'category' => 'Fish Market', 'address' => 'Морска градина, Варна', 'phone' => '+359987100200', 'website' => null, 'email' => null, 'review_rating' => 4.7],
            ['title' => 'Фитнес Сила', 'category' => 'Gym', 'address' => 'ул. Якубица 1, Благоевград', 'phone' => '+359986300400', 'website' => null, 'email' => null, 'review_rating' => 4.9],
            ['title' => 'Ателие за шиене Игла', 'category' => 'Tailor', 'address' => 'ул. Гурко 9, Велико Търново', 'phone' => '+359985500600', 'website' => null, 'email' => null, 'review_rating' => 4.7],
            ['title' => 'Счетоводна кантора Баланс', 'category' => 'Accounting', 'address' => 'бул. Цар Освободител 44, Варна', 'phone' => '+359984700800', 'website' => null, 'email' => 'contact@balans-acc.bg', 'review_rating' => 4.8],
        ];

        foreach ($leads as $data) {
            $lead = Lead::create(array_merge($data, [
                'status' => 'new',
                'source' => 'csv',
                'assignee_id' => $admin->id,
                'import_batch_id' => $batch->id,
            ]));

            // Apply tags
            $tagIds = [];
            if ($lead->review_rating >= 4.5) {
                $tagIds[] = $highRatingTag->id;
            }
            if (is_null($lead->website)) {
                $tagIds[] = $noWebsiteTag->id;
            }
            if ($lead->review_rating > 4.5 && is_null($lead->website)) {
                $tagIds[] = $webDevTag->id;
            }
            $lead->tags()->sync($tagIds);
        }
    }
}
