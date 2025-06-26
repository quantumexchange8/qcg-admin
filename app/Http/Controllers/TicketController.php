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
                $ticket_attachments = $ticket->getMedia('ticket_attachments');

                return [
                    'ticket_id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
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

    public function getTicketDetails(Request $request)
    {
        // $ticket_id = $request->query('ticket_id');
        $ticket = Ticket::with(['replies.user', 'user'])->where('id', $request->ticket_id)->get()
                    ->map(function($ticket_details) {
                        $ticket_attachments = $ticket_details->getMedia('ticket_attachments');

                        $replies = [
                            'reply_id' => $ticket_details->replies->id,
                            'name' => $ticket_details->replies->user->chinese_name ?? $ticket_details->replies->user->first_name,
                            'subject' => $ticket_details->replies->message,
                            'sent_at' => $ticket_details->replies->created_at,
                            'reply_attachments' => $ticket_details->replies->getMedia('reply_attachments'),
                        ];

                        return [
                            'ticket_id' => $ticket_details->id,
                            'subject' => $ticket_details->subject,
                            'description' => $ticket_details->description,
                            'ticket_attachments' => $ticket_attachments,
                            'replies' => $replies,
                            'created_at' => $ticket_details->created_at,
                            'ticket_details' => $ticket->status,
                        ];

                    })
                    ->values();

        return response()->json([
            'ticket' => $ticket,
        ]);
    }

    public function submitReply(Request $request)
    {
        TicketReply::create([
            'ticket_id' => $request->ticket_id,
            'user_id' => $request->user_id,
            'message' => $request->message,
        ]);
        // $ticket_id = $request->query('ticket_id');
        // $ticket = Ticket::with(['replies.user', 'user'])->where('id', $request->ticket_id)->get()
        //             ->map(function($ticket_details) {
        //                 $ticket_attachments = $ticket_details->getMedia('ticket_attachments');

        //                 $replies = [
        //                     'reply_id' => $ticket_details->replies->id,
        //                     'name' => $ticket_details->replies->user->chinese_name ?? $ticket_details->replies->user->first_name,
        //                     'subject' => $ticket_details->replies->message,
        //                     'sent_at' => $ticket_details->replies->created_at,
        //                     'reply_attachments' => $ticket_details->replies->getMedia('reply_attachments'),
        //                 ];

        //                 return [
        //                     'ticket_id' => $ticket_details->id,
        //                     'subject' => $ticket_details->subject,
        //                     'description' => $ticket_details->description,
        //                     'ticket_attachments' => $ticket_attachments,
        //                     'replies' => $replies,
        //                     'created_at' => $ticket_details->created_at,
        //                     'ticket_details' => $ticket->status,
        //                 ];

        //             })
        //             ->values();

        // return response()->json([
        //     'ticket' => $ticket,
        // ]);
    }
}
