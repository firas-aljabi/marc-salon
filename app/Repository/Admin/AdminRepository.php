<?php

namespace App\Repository\Admin;

use App\ApiHelper\SortParamsHelper;
use App\Filter\Transfer\TransferFilter;
use App\Models\Expert;
use App\Models\Holiday;
use App\Models\Service;
use App\Models\Transfer;
use App\Models\User;
use App\Repository\BaseRepositoryImplementation;
use App\Statuses\HavePermission;
use App\Statuses\PermissionType;
use App\Statuses\UserType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminRepository extends BaseRepositoryImplementation
{
    public function getFilterItems($filter)
    {

        $records = Transfer::query();

        if ($filter instanceof TransferFilter) {

            $records->when(isset($filter->starting_date), function ($query) use ($filter) {
                $query->where('date', $filter->getStartingDate());
            });
            $records->when(isset($filter->end_date), function ($query) use ($filter) {
                $query->where('date', $filter->getEndingDate());
            });

            $records->when((isset($filter->starting_date) && isset($filter->end_date)), function ($records) use ($filter) {
                $records->whereBetween('date', [$filter->getStartingDate(), $filter->getEndingDate()])
                    ->orWhereBetween('date', [$filter->getStartingDate(), $filter->getEndingDate()]);
            });

            $records->when(isset($filter->transfer_amount), function ($records) use ($filter) {
                $records->where('transfer_amount', 'LIKE', '%' . $filter->getTransferAmount() . '%');
            });

            $records->when(isset($filter->user_id), function ($records) use ($filter) {
                $records->whereHas('user', function ($q) use ($filter) {
                    return $q->where('id', $filter->getUserId());
                });
            });
            $records->when(isset($filter->client_id), function ($records) use ($filter) {
                $records->whereHas('client', function ($q) use ($filter) {
                    return $q->where('id', $filter->getClientId());
                });
            });

            return $records->with(['client', 'user'])->paginate($filter->per_page);
        }

        return $records->with(['client', 'user'])->paginate($filter->per_page);
    }

    public function model()
    {
        return Transfer::class;
    }
    public function create_admin($data)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'type' => UserType::ADMIN
            ]);
            DB::commit();
            return $user;
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }
    public function create_receiption($data)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'type' => UserType::RECEPTION
            ]);

            DB::commit();
            return $user;
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }

    public function create_transfer($data)
    {
        DB::beginTransaction();
        try {
            $transfer = new Transfer();
            if (Arr::has($data, 'attachment')) {
                $file = Arr::get($data, 'attachment');
                $extention = $file->getClientOriginalExtension();
                $file_name = Str::uuid() . date('Y-m-d') . '.' . $extention;
                $file->move(public_path('images'), $file_name);
                $image_file_path = public_path('images/' . $file_name);
                $image_data = file_get_contents($image_file_path);
                $base64_image = base64_encode($image_data);
                $transfer->attachment = $base64_image;
            }
            $transfer->user_id = auth()->user()->id;
            $transfer->client_id = $data['client_id'];
            $transfer->date = $data['date'];
            $transfer->transfer_amount = $data['transfer_amount'];
            $transfer->save();
            DB::commit();
            return $transfer->load(['user', 'client']);
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }

    public function update_transfer($data)
    {
        DB::beginTransaction();
        try {

            $transfer = $this->updateById($data['transfer_id'], $data);

            if (Arr::has($data, 'attachment')) {
                $file = Arr::get($data, 'attachment');
                $extension = $file->getClientOriginalExtension();
                $file_name = Str::uuid() . date('Y-m-d') . '.' . $extension;
                $file->move(public_path('attachments'), $file_name);
                $image_file_path = public_path('attachments/' . $file_name);
                $image_data = file_get_contents($image_file_path);
                $base64_image = base64_encode($image_data);
                $transfer->attachment = $base64_image;
                $transfer->save();
            }
            DB::commit();
            if ($transfer === null) {
                return response()->json(['message' => "Transfer was not Updated"]);
            }
            return $transfer->load('user', 'client');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return response()->json(['message' => $e->getMessage()]);
        }
    }
    public function create_service($data)
    {
        DB::beginTransaction();
        try {
            $service = Service::create([
                'name' => $data['name'],
            ]);
            DB::commit();
            return $service;
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }

    public function create_expert($data)
    {
        DB::beginTransaction();
        try {
            $expert = new Expert();
            if (Arr::has($data, 'image')) {
                $file = Arr::get($data, 'image');
                $extention = $file->getClientOriginalExtension();
                $file_name = Str::uuid() . date('Y-m-d') . '.' . $extention;
                $file->move(public_path('images'), $file_name);
                $image_file_path = public_path('images/' . $file_name);
                $image_data = file_get_contents($image_file_path);
                $base64_image = base64_encode($image_data);
                $expert->image = $base64_image;
            }
            $expert->name = $data['name'];
            $expert->position = $data['position'];
            $expert->save();


            $expert->services()->attach($data['services']);

            DB::commit();
            return $expert->load('services');
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }

    public function list_of_experts($filter)
    {
        $records = Expert::query();
        return $records->with(['services', 'holidays', 'reservations'])->paginate($filter->per_page);
    }

    public function list_of_services($filter)
    {
        $records = Service::query();
        return $records->paginate($filter->per_page);
    }
    public function list_of_receiptions($filter)
    {
        $records = User::query()->where('type', UserType::RECEPTION);
        return $records->paginate($filter->per_page);
    }

    public function create_holiday($data)
    {
        DB::beginTransaction();
        try {
            $existHoliday = Holiday::where('date', $data['date'])->where('expert_id', $data['expert_id'])->first();
            if (!$existHoliday) {
                $holiday = Holiday::create([
                    'date' => $data['date'],
                    'expert_id' => $data['expert_id'],
                ]);
            }
            DB::commit();
            if ($holiday != null) {
                return $holiday;
            } else {
                $holiday->load('expert');
            }
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }

    public function chang_permission($data)
    {
        DB::beginTransaction();
        try {
            $receiption = User::where('id', $data['receiption_id'])->first();

            if ($receiption->type == UserType::RECEPTION) {
                if ($data['type'] == PermissionType::UPDATE) {
                    $receiption->update([
                        'permission_to_update' => HavePermission::TRUE
                    ]);
                } else {
                    $receiption->update([
                        'permission_to_delete' => HavePermission::TRUE
                    ]);
                }
            } else {
                return 'Failed';
            }

            DB::commit();
            return $receiption;
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }
}
