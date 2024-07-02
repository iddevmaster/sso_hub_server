<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FetchPOSDataToDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:posdata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch data from POS Trainingzenter';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiUrl = 'https://pos.trainingzenter.com/api/student/2024-05-20';
        // $apiUrl = 'https://pos.trainingzenter.com/api/student/' . date("Y-m-d");

        try {
            $response = Http::withHeaders([
                "Authorization" => "Basic YWRtaW50ejpRYkh2NGJxZA=="
            ])->get($apiUrl);
            $responseData = $response->json();

            if ($response->successful()) {
                $this->processDataAndStore($responseData);
                $this->info('Data fetched and stored successfully!');
            } else {
                $this->error('API request failed with status code: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('An error occurred during the API request: ' . $e->getMessage());
        }
    }

    /**
     * Process the fetched data and store it in the database.
     *
     * @param array $responseData The data retrieved from the API
     *
     * @return void
     */
    private function processDataAndStore(array $responseData)
    {
        echo "start process data and store\n";
        $agn = Agency::where('prefix', 'TZ')->first();
        if (!$agn) {
            $agn = Agency::create([
                'name' => 'TrainingZenter',
                'prefix' => 'TZ',
            ]);
        }
        $brn = Branch::where('name', 'TrainingZenter')->first();
        if (!$brn) {
            $brn = Branch::create([
                'name' => 'TrainingZenter',
                'agn' => $agn->id,
            ]);
        }
        echo "Branch: " . $brn->name . " / " . $agn->name . "\n";

        foreach ($responseData as $dataItem) {
            $course = Course::where('code', $dataItem['course_code'])->first();
            if (!$course) {
                try {
                    $course = Course::create([
                        'code' => $dataItem['course_code'],
                        'name' => $dataItem['course_name_th'],
                        'from' => 3,
                        'agn' => $agn->id,
                    ]);
                } catch (\Throwable $th) {
                    echo "Create course error: " . $th->getMessage() . "\n";
                }
            }
            echo "Course: " . $course->code . " Student: " . count($dataItem['data']) . "\n";
            if (count($dataItem['data']) > 0) {
                foreach ($dataItem['data'] as $student) {
                    $customer = User::where('username', $student['student_identification_number'])->first();

                    if (!$customer) {
                        try {
                            $customer = User::create([
                                'username' => $student['student_identification_number'],
                                'password' => Hash::make($student['student_identification_number']),
                                'prefix' => $student['student_prefix_th'],
                                'name' => $student['student_firstname_th'],
                                'lname' => $student['student_lastname_th'],
                                'brn' => $brn->name,
                                'agn' => $agn->name,
                                'role' => 'customer'
                            ]);
                        } catch (\Throwable $th) {
                            echo "Create customer error: " . $th->getMessage() . "\n";
                        }
                    }

                    try {
                        $cus_course = $customer->course;
                        if (count($cus_course ?? []) > 0) {
                            if (!in_array($course->id, $cus_course)) {
                                $cus_course[] = $course->id;
                                $customer->course = json_encode($cus_course);
                            }
                        } else {
                            $customer->course = json_encode([$course->id]);
                        }

                        if (!$customer->hasRole('customer')) {
                            $customer->assignRole('customer');
                        }

                        $customer->save();
                    } catch (\Throwable $th) {
                        echo "Update customer error: " . $th->getMessage() . "\n";
                    }

                    echo "customer: " . $customer->username . "\n";
                }
            }
            echo "Update data success!! \n";
            echo "=====================\n";
        }
    }
}
