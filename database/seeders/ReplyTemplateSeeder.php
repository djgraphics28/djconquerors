<?php

namespace Database\Seeders;

use App\Models\ReplyTemplate;
use App\Models\ReplyTemplateItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReplyTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Template 1: Standard Format
        $template1 = ReplyTemplate::create([
            'name' => 'First Reply to Martin - Standard',
            'description' => 'Standard format for first reply to Sir Martin',
            'order' => 1,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template1->id,
            'title' => 'User Information',
            'content' => "Your own Riscoin account ID: {riscoin_id}\nDeposit Amount: \${invested_amount}\nMy Name: {name}\nLanguage: English, Tagalog\nNationality: Filipino\nAge: {age}\nGender: {gender}\nInviter: {inviters_code}\nAssistant: {assistant.riscoin_id}",
            'order' => 1,
            'is_active' => true,
        ]);

        // Template 2: Detailed Format
        $template2 = ReplyTemplate::create([
            'name' => 'First Reply to Martin - Detailed',
            'description' => 'More detailed format with personal information',
            'order' => 2,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template2->id,
            'title' => 'Greeting',
            'content' => "Hello Sir Martin,\n\nHere is my information:",
            'order' => 1,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template2->id,
            'title' => 'Personal Details',
            'content' => "Riscoin ID: {riscoin_id}\nFull Name: {name}\nEmail: {email}\nDeposit Amount: \${invested_amount}\nLanguage: English, Tagalog\nNationality: Filipino\nAge: {age}\nGender: {gender}\nInviter Code: {inviters_code}\nAssistant ID: {assistant.riscoin_id}",
            'order' => 2,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template2->id,
            'title' => 'Closing',
            'content' => "Thank you!",
            'order' => 3,
            'is_active' => true,
        ]);

        // Template 3: Professional Format
        $template3 = ReplyTemplate::create([
            'name' => 'First Reply to Martin - Professional',
            'description' => 'Professional tone with greeting',
            'order' => 3,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template3->id,
            'title' => 'Professional Greeting',
            'content' => "Dear Sir Martin,\n\nI hope this message finds you well. Please find my registration details below:",
            'order' => 1,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template3->id,
            'title' => 'Registration Details',
            'content' => "• Riscoin Account ID: {riscoin_id}\n• Full Name: {name}\n• Email Address: {email}\n• Initial Deposit: \${invested_amount}\n• Preferred Languages: English, Tagalog\n• Nationality: Filipino\n• Age: {age}\n• Gender: {gender}\n• Referred by: {inviters_code}\n• Assistant: {assistant.riscoin_id}",
            'order' => 2,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template3->id,
            'title' => 'Professional Closing',
            'content' => "Looking forward to working with you.\n\nBest regards,\n{name}",
            'order' => 3,
            'is_active' => true,
        ]);

        // Template 4: Welcome Message
        $template4 = ReplyTemplate::create([
            'name' => 'Welcome Message',
            'description' => 'Welcome message for new members',
            'order' => 4,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template4->id,
            'title' => 'Welcome',
            'content' => "Welcome {name}!",
            'order' => 1,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template4->id,
            'title' => 'Confirmation',
            'content' => "Thank you for joining our team. Your Riscoin ID is {riscoin_id}.\n\nYour initial investment of \${invested_amount} has been recorded.",
            'order' => 2,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template4->id,
            'title' => 'Support',
            'content' => "If you have any questions, feel free to reach out!\n\nBest regards,\nThe Team",
            'order' => 3,
            'is_active' => true,
        ]);

        // Template 5: Martin Support Form (Original Format)
        $template5 = ReplyTemplate::create([
            'name' => 'Martin Support Form',
            'description' => 'Original format for Sir Martin with mixed static and dynamic content',
            'order' => 5,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template5->id,
            'title' => 'Account ID',
            'content' => 'Your own Riscoin account ID: {riscoin_id}',
            'order' => 1,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template5->id,
            'title' => 'Deposit Amount',
            'content' => 'Deposit Amount: ${invested_amount}',
            'order' => 2,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template5->id,
            'title' => 'Name',
            'content' => 'My Name: {name}',
            'order' => 3,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template5->id,
            'title' => 'Language',
            'content' => 'Language: English, Tagalog',
            'order' => 4,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template5->id,
            'title' => 'Nationality',
            'content' => 'Nationality: Filipino',
            'order' => 5,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template5->id,
            'title' => 'Age',
            'content' => 'Age: {age}',
            'order' => 6,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template5->id,
            'title' => 'Gender',
            'content' => 'Gender: {gender}',
            'order' => 7,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template5->id,
            'title' => 'Inviter',
            'content' => 'Inviter: {inviters_code}',
            'order' => 8,
            'is_active' => true,
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $template5->id,
            'title' => 'Assistant',
            'content' => 'Assistant: {assistant.riscoin_id}',
            'order' => 9,
            'is_active' => true,
        ]);
    }
}
