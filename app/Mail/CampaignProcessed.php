<?php

namespace App\Mail;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignProcessed extends Mailable
{
    use Queueable, SerializesModels;

    public $campaign;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Campaign has been Processed')
                    ->view('emails.campaign_processed')
                    ->with(['campaignName' => $this->campaign->name]);
    }
}
