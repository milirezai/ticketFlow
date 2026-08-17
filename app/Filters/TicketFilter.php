<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TicketFilter
{
    public function search(Builder $query, array $filters)
    {
        $filters = collect($filters);

        return $query->when($filters->has('title'), function (Builder $query) use ($filters){
            $query->subject($filters->get('title'));
        })
            ->when($filters->has('status'), function (Builder $query) use ($filters){
                $query->status($filters->get('status'));
            })
            ->when($filters->has('priority'), function (Builder $query) use ($filters){
                $query->priority($filters->get('priority'));
            })
            ->when($filters->has('owner'), function (Builder $query) use ($filters){
                $query->owner($filters->get('owner'));
            })
            ->when($filters->has('category'), function (Builder $query) use ($filters){
                $query->category($filters->get('category'));
            })
            ->when($filters->has('dateFrom'), function (Builder $query) use ($filters){
                $query->dateFrom($filters->get('dateFrom'));
            })
            ->when($filters->has('dateTo'), function (Builder $query) use ($filters){
                $query->dateTo($filters->get('dateTo'));
            })
            ->when($filters->has('assignedTo'), function (Builder $query) use ($filters){
                $query->assignedTo($filters->get('assignedTo'));
            });
    }
}
