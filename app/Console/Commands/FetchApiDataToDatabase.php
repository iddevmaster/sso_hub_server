<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\Course;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class FetchApiDataToDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:apidata {api_dest}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches data from an API each branch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiDest = $this->argument('api_dest');
        $apiUrl = 'http://www.dsmsys.net/'. $apiDest . '/tz/?date=' . date("Y-m-d");
        try {

            $response = Http::withHeaders([
                "Authorization" => "Basic YWRtaW50ejpRYkh2NGJxZA=="
            ])->get($apiUrl);
            $responseData = $response->json();

            if ($response->successful()) {
                $this->processDataAndStore($responseData, $apiDest);
                $this->info('Data fetched and stored successfully!');
            } else {
                $this->error('API request failed with status code: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('An error occurred during the API request: ' . $e->getMessage());
        }

        return 0;
    }


    /**
     * Process the fetched data and store it in the database.
     *
     * @param array $responseData The data retrieved from the API
     *
     * @return void
     */
    private function processDataAndStore(array $responseData, $apiDest)
    {
        echo "start process data and store\n";
        $agn = Agency::where('prefix', 'IDD')->first();
        switch ($apiDest) {
            case 'idmskk':
                $brn = Branch::where('name', 'โนนทัน')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'โนนทัน',
                        'agn' => $agn->id,
                    ]);
                }
                break;

            case 'idmsLLK':
                $brn = Branch::where('name', 'ลำลูกกา')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'ลำลูกกา',
                        'agn' => $agn->id,
                    ]);
                }
                break;

            case 'idmsMK':
                $brn = Branch::where('name', 'มหาสารคาม')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'มหาสารคาม',
                        'agn' => $agn->id,
                    ]);
                }
                break;

            case 'idmsPRO':
                $brn = Branch::where('name', 'เดอะโปรเฟชชั่นแนล')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'เดอะโปรเฟชชั่นแนล',
                        'agn' => $agn->id,
                    ]);
                }
                break;

            case 'idmsPY':
                $brn = Branch::where('name', 'พยัคฆภูมิพิสัย')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'พยัคฆภูมิพิสัย',
                        'agn' => $agn->id,
                    ]);
                }
                break;

            case 'idmsTK':
                $brn = Branch::where('name', 'แก่งคอย')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'แก่งคอย',
                        'agn' => $agn->id,
                    ]);
                }
                break;

            default:
                $brn = Branch::where('name', 'โนนทัน')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'โนนทัน',
                        'agn' => $agn->id,
                    ]);
                }
                break;
        }
        echo "Branch: " . $brn->name . " / " . $agn->name . "\n";

        foreach ($responseData as $dataItem) {
            $course = Course::where('code', $dataItem['course_code'])->first();
            if (!$course) {
                try {
                    $course = Course::create([
                        'code' => $dataItem['course_code'],
                        'name' => $dataItem['course_name_th'],
                        'from' => 2,
                        'agn' => $agn->id,
                    ]);
                } catch (\Throwable $th) {
                    echo "Create course error: " . $th->getMessage() . "\n";
                }
            }
            echo "Course: " . $course->code . "\n";
            if (count($dataItem['data']) > 0) {
                foreach ($dataItem['data'] as $student) {
                    $customer = User::where('email', $student['student_identification_number'])->first();

                    if (!$customer) {
                        try {
                            $customer = User::create([
                                'email' => $student['student_identification_number'],
                                'password' => Hash::make($student['student_identification_number']),
                                'name' => $student['student_firstname_th'] . ($student['student_lastname_th'] ? ' ' . $student['student_lastname_th'] : '' ),
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

                    echo "customer: " . $customer->email . "\n";
                }
            }
            echo "Update data success!! \n";
            echo "=====================\n";
        }
    }
}
