<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MemberListingExport implements FromCollection, WithHeadings
{
    protected $members;

    public function __construct($members)
    {
        $this->members = $members;
    }

    public function collection()
    {
        $records = $this->members->with(['upline:id,first_name', 'teamHasUser.team'])->select([
            'id',
            'first_name',
            'email',
            'id_number',
            'upline_id',
            'created_at',
        ])->orderByDesc('id')->get();
        $result = [];

        foreach ($records as $record) {
            $result[] = [
                'Name' => $record->first_name,
                'Email' => $record->email,
                'Id Number' => $record->id_number,
                'Team' => $record->teamHasUser->team->name ?? '',
                'Upline' => $record->upline->first_name ?? '',
                'Joined Date' => $record->created_at ? $record->created_at->format('Y-m-d') : '',
            ];
        }

        return collect($result);
    }


    public function headings(): array
    {
        return ['Name', 'Email', 'Id Number', 'Team', 'Upline', 'Joined Date'];
    }
}
