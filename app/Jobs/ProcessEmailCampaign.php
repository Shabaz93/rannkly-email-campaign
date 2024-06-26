<?php
namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class ProcessEmailCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function handle()
    {
        echo "Heloo"; die;
        $chunkSize = 50; // Number of emails to process in each chunk
        $totalContacts = Contact::where('campaign_id', $this->campaign->id)->count();
        $chunks = ceil($totalContacts / $chunkSize);

        for ($i = 0; $i < $chunks; $i++) {
            $contacts = Contact::where('campaign_id', $this->campaign->id)
                ->skip($i * $chunkSize)
                ->take($chunkSize)
                ->get();

            foreach ($contacts as $contact) {
                Mail::send([], [], function ($message) use ($contact) {
                    $message->to($contact->email)
                            ->subject('Your Subject Here')
                            ->setBody('<h1>Hello, ' . $contact->name . '</h1><p>Your email content here.</p>', 'text/html');
                });
            }

            // Update campaign progress
            DB::table('campaigns')
                ->where('id', $this->campaign->id)
                ->update(['processed_contacts' => ($i + 1) * $chunkSize]);
        }

        // Mark the campaign as completed
        DB::table('campaigns')
            ->where('id', $this->campaign->id)
            ->update(['status' => 'completed']);



        // Notify user upon completion
        Mail::to($this->campaign->user->email)->send(new \App\Mail\CampaignProcessed($this->campaign));
    }
}
