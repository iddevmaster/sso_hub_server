<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\Course;
use App\Models\User;
use App\Models\User_has_course;
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

            if ($response->successful()) {
                $responseData = $response->json();
                $this->processDataAndStore($responseData, $apiDest);
                $this->info('Data fetched and stored successfully! ' . date("Y-m-d"));
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
        echo "start process data and store dest: ". $apiDest ."\n";

        $agn = Agency::where('prefix', 'IDD')->orWhere('agn_id', "IDD0002")->orWhere('name', "โรงเรียนสอนขับรถ ไอดี ไดร์ฟเวอร์")->first();
        if (!$agn) {
            $agn = Agency::create([
                'prefix' => 'IDD',
                'agn_id' => 'IDD0002',
                'name' => 'โรงเรียนสอนขับรถ ไอดี ไดร์ฟเวอร์',
            ]);
        } else {
            $agn->update([
                'agn_id' => 'IDD0002',
            ]);
        }
        echo "agn: ". $agn->id ?? 'agn not found' ."\n";
        switch ($apiDest) {
            case 'idmskk':
                $brn = Branch::where('name', 'โนนทัน')->orWhere('brn_id', 'IDD0003')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'โนนทัน',
                        'brn_id' => 'IDD0003', // 'IDD0003' is the branch id for 'โนนทัน
                        'agn' => $agn->id,
                    ]);
                } else {
                    $brn->update([
                        'brn_id' => "IDD0003"
                    ]);
                }
                break;

            case 'idmsLLK':
                $brn = Branch::where('name', 'ลำลูกกา')->orWhere('brn_id', 'IDD0004')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'ลำลูกกา',
                        'brn_id' => 'IDD0004',
                        'agn' => $agn->id,
                    ]);
                } else {
                    $brn->update([
                        'brn_id' => "IDD0004"
                    ]);
                }
                break;

            case 'idmsMK':
                $brn = Branch::where('name', 'มหาสารคาม')->orWhere('brn_id', 'IDD0005')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'มหาสารคาม',
                        'brn_id' => 'IDD0005',
                        'agn' => $agn->id,
                    ]);
                } else {
                    $brn->update([
                        'brn_id' => "IDD0005"
                    ]);
                }
                break;

            case 'idmsPRO':
                $brn = Branch::where('name', 'เดอะโปรเฟชชั่นแนล')->orWhere('brn_id', 'IDD0006')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'เดอะโปรเฟชชั่นแนล',
                        'brn_id' => 'IDD0006',
                        'agn' => $agn->id,
                    ]);
                } else {
                    $brn->update([
                        'brn_id' => "IDD0006"
                    ]);
                }
                break;

            case 'idmsPY':
                $brn = Branch::where('name', 'พยัคฆภูมิพิสัย')->orWhere('brn_id', 'IDD0002')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'พยัคฆภูมิพิสัย',
                        'brn_id' => 'IDD0002',
                        'agn' => $agn->id,
                    ]);
                } else {
                    $brn->update([
                        'brn_id' => "IDD0002"
                    ]);
                }
                break;

            case 'idmsTK':
                $brn = Branch::where('name', 'แก่งคอย')->orWhere('brn_id', 'IDD0007')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'แก่งคอย',
                        'brn_id' => 'IDD0007',
                        'agn' => $agn->id,
                    ]);
                } else {
                    $brn->update([
                        'brn_id' => "IDD0007"
                    ]);
                }
                break;

            default:
                $brn = Branch::where('name', 'โนนทัน')->orWhere('brn_id', 'IDD0003')->first();
                if (!$brn) {
                    $brn = Branch::create([
                        'name' => 'โนนทัน',
                        'brn_id' => 'IDD0003',
                        'agn' => $agn->id,
                    ]);
                } else {
                    $brn->update([
                        'brn_id' => "IDD0003"
                    ]);
                }
                break;
        }
        echo "Branch: " . $brn->name ?? 'brn name not found' . " / " . $agn->name ?? 'agn name not found' . "\n";

        foreach ($responseData as $dataItem) {
            $course = Course::where('code', $dataItem['course_code'])->first();
            if (!$course) {
                try {
                    $course = Course::create([
                        'code' => $dataItem['course_code'],
                        'name' => $dataItem['course_name_th'],
                        'from' => 2,
                        'agn' => $agn->agn_id,
                    ]);
                } catch (\Throwable $th) {
                    echo "Create course error: " . $th->getMessage() . "\n";
                }
            } else {
                $course->update([
                    'agn' => $agn->agn_id,
                ]);
            }

            echo "Course: " . $course->code . "\n";
            echo "Student found: " . count($dataItem['data']) . "\n";
            if (count($dataItem['data']) > 0) {
                foreach ($dataItem['data'] as $student) {
                    $customer = User::where('username', $student['student_identification_number'])->first();

                    if (!$customer) {
                        try {
                            $student_id = $student['student_code'];
                            $national = 'TH';
                            if ($student_id) {
                                $split_code = $student['student_code'][0] . $student['student_code'][1];
                                switch ($split_code) {
                                    case 'PK':
                                        $national = "PK";
                                        break;
                                    case 'PL':
                                        $national = "PL";
                                        break;
                                    case 'PM':
                                        $national = "PM";
                                        break;
                                    default:
                                        $national = "TH";
                                        break;
                                }
                                echo "National: " . $national . "\n";
                            }
                            $customer = User::create([
                                'username' => $student['student_identification_number'],
                                'password' => Hash::make($student['student_identification_number']),
                                'prefix' => $student['student_prefix_th'],
                                'name' => $student['student_firstname_th'],
                                'lname' => $student['student_lastname_th'],
                                'brn' => $brn->brn_id,
                                'agn' => $agn->agn_id,
                                'role' => 'customer',
                                'prefix_eng' => $student['student_prefix_eng'],
                                'fname_eng' => $student['student_firstname_eng'],
                                'lname_eng' => $student['student_lastname_eng'],
                                'learning_status' => $student['student_learning_status'],
                                'nationality' => $national ?? 'TH',
                            ]);
                        } catch (\Throwable $th) {
                            echo "Create customer error: " . $th->getMessage() . "\n";
                        }
                    }

                    try {
                        // $cus_course = $customer->course;
                        // if (count($cus_course ?? []) > 0) {
                        //     if (!in_array($course->id, $cus_course)) {
                        //         $cus_course[] = $course->id;
                        //         $customer->course = json_encode($cus_course);
                        //     }
                        // } else {
                        //     $customer->course = json_encode([$course->id]);
                        // }

                        $user_has_course = User_has_course::where('user_id', $customer->id)->where('course_id', $course->id)->count();
                        if (!$user_has_course) {
                            User_has_course::create([
                                'user_id' => $customer->id,
                                'course_id' => $course->id
                            ]);
                            echo "Create User_has_course success!! \n";
                        } else {
                            echo "User " . $customer->id . "and course " . $course->id . " has exist \n";
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
