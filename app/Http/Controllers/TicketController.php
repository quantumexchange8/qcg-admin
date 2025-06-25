<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketReply;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    public function pending()
    {
        $categories = TicketCategory::orderBy('order')
            ->get()
            ->map(function($ticket_category) {
                $category = json_decode($ticket_category->category, true);

                return [
                    'category_id' => $ticket_category->id,
                    'category' => $category,
                ];

            })
            ->values();

        return Inertia::render('Tickets/Pending', [
            'categories' => $categories,
        ]);

    }

    public function history()
    {
        return Inertia::render('Tickets/History');
    }

    public function getPendingTickets(Request $request)
    {
        $status = $request->query('status');
        $category_id = $request->query('category_id');

        Log::info($request);
        $query = Ticket::with(['category', 'user']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($category_id) {
            $query->whereHas('category', function ($query) use ($category_id) {
                $query->where('id', $category_id);
            });
        }

        $tickets = $query->get()
            ->map(function($ticket) {
                $category = json_decode($ticket->category->category, true);

                return [
                    'ticket_id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'name' => $ticket->user->chinese_name ?? $ticket->user->first_name,
                    'email' => $ticket->user->email,
                    'created_at' => $ticket->created_at,
                    'category' => $category,
                    'status' => $ticket->status,
                ];

            })
            ->values();

        return response()->json([
            'tickets' => $tickets,
        ]);
    }

    public function getTicketHistory(Request $request)
    {


        return response()->json([
            'tickets' => $tickets,
        ]);
    }
}
