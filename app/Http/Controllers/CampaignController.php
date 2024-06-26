<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Campaign;
use App\Models\Contact;
use App\Jobs\ProcessEmailCampaign;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    /**
     * Display the campaign creation form.
     */
    public function create(): Response
    {
        return Inertia::render('Campaign/Create');
    }

    /**
     * Store a newly created campaign in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'csv_file' => 'required|mimes:csv,txt',
        ]);


        if ($validator->fails()) {
            return Redirect::route('campaign.create')
                ->withErrors($validator)
                ->withInput();
        }

        // Check if a campaign with the same name already exists for the user
        $existingCampaign = Campaign::where('name', $request->name)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingCampaign) {
            return Redirect::route('campaign.create')
                ->withErrors(['name' => 'A campaign with this name already exists.'])
                ->withInput();
        }


        $campaign = new Campaign();
        $campaign->name = $request->name;
        $campaign->user_id = Auth::id();
        $campaign->status = 'processing';
        $campaign->processed_contacts = 0;
        $campaign->save();

        $path = $request->file('csv_file')->store('csv_files');

        // Process the CSV file (this should be done in a queue for better performance)
        $this->processCSV($path, $campaign->id);

        // Dispatch the job to process the email campaign
        ProcessEmailCampaign::dispatch($campaign);

        return Redirect::route('campaign.create')->with('status', 'Campaign created successfully!');
    }

    public function progress(Request $request)
    {
        $campaign = Campaign::where('user_id', Auth::id())->latest()->first();

        if ($campaign) {
            $totalContacts = $campaign->contacts()->count();
            $processedContacts = $campaign->processed_contacts;
            $status = $campaign->status;

            return response()->json([
                'total_contacts' => $totalContacts,
                'processed_contacts' => $processedContacts,
                'status' => $status,
            ]);
        }

        return response()->json(['message' => 'No campaigns found'], 404);
    }

    /**
     * Process the uploaded CSV file and store contacts.
     */
    protected function processCSV(string $path, int $campaignId): void
    {
        $file = Storage::get($path);
        $rows = array_map('str_getcsv', explode("\n", $file));
        $header = array_shift($rows);

        foreach ($rows as $row) {
            if (count($row) === count($header)) {
                $row = array_combine($header, $row);
                if (!empty($row['name']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    Contact::create([
                        'campaign_id' => $campaignId,
                        'name' => $row['name'],
                        'email' => $row['email'],
                    ]);
                }
            }
        }
    }
}
