<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketLog;
use App\Models\TicketReply;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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

        return Inertia::render('Tickets/History', [
            'categories' => $categories,
        ]);
    }

    public function getPendingTickets(Request $request)
    {
        $status = $request->query('status');
        $category_id = $request->query('category_id');

        $query = Ticket::with(['category', 'user', 'replies', 'read'])->whereNot('status', 'resolved');

        if ($status) {
            $query->where('status', $status);
        }

        if ($category_id) {
            $query->whereHas('category', function ($query) use ($category_id) {
                $query->where('id', $category_id);
            });
        }

        $tickets = $query->orderByRaw('COALESCE(last_replied_at, created_at) DESC')
        ->get()
            ->map(function($ticket) {
                $category = json_decode($ticket->category->category, true);
                $ticket_attachments = $ticket->getMedia('ticket_attachment');

                $lastReply = $ticket->replies->sortByDesc('created_at')->first();
                $lastRead = $ticket->read->where('user_id', Auth::id())->first();

                $read_status = $lastRead ? ($lastReply ? ($lastReply->created_at < $lastRead->date_read) : true) : false;

                return [
                    'ticket_id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
                    'name' => $ticket->user->chinese_name ?? $ticket->user->first_name,
                    'email' => $ticket->user->email,
                    'created_at' => $ticket->created_at,
                    'category' => $category,
                    'status' => $ticket->status,
                    'ticket_attachments' => $ticket_attachments,
                    'read_status' => $read_status,
                ];

            })
            ->values();

        return response()->json([
            'tickets' => $tickets,
        ]);
    }

    public function getTicketHistory(Request $request)
    {
        $category_id = $request->query('category_id');

        $query = Ticket::with(['category', 'user'])->where('status', 'resolved');

        if ($category_id) {
            $query->whereHas('category', function ($query) use ($category_id) {
                $query->where('id', $category_id);
            });
        }

        $tickets = $query->orderByRaw('COALESCE(closed_at, last_replied_at, created_at) DESC')
            ->get()
            ->map(function($ticket) {
                $category = json_decode($ticket->category->category, true);
                $ticket_attachments = $ticket->getMedia('ticket_attachment');

                return [
                    'ticket_id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
                    'name' => $ticket->user->chinese_name ?? $ticket->user->first_name,
                    'email' => $ticket->user->email,
                    'created_at' => $ticket->created_at,
                    'closed_at' => $ticket->closed_at,
                    'category' => $category,
                    'status' => $ticket->status,
                    'ticket_attachments' => $ticket_attachments,
                ];

            })
            ->values();

        return response()->json([
            'tickets' => $tickets,
        ]);
    }

    public function getTicketReplies(Request $request)
    {
        // $ticket_id = $request->query('ticket_id');
        $ticket = Ticket::with(['replies.user:id,first_name,chinese_name,email', 'user:id,first_name,chinese_name,email'])->where('id', $request->ticket_id)->get()
                    ->map(function($ticket_details) {
                        $ticket_attachments = $ticket_details->getMedia('ticket_attachment');

                        $replies = $ticket_details->replies->map(function ($reply) {
                            return [
                                'reply_id' => $reply->id,
                                'user_id' => $reply->user_id,
                                'name' => $reply->user->chinese_name ?? $reply->user->first_name,
                                'message' => $reply->message,
                                'sent_at' => $reply->created_at,
                                'reply_attachments' => $reply->getMedia('ticket_attachment'),
                            ];
                        });

                        return [
                            'ticket_id' => $ticket_details->id,
                            'user_id' => $ticket_details->user_id,
                            'subject' => $ticket_details->subject,
                            'description' => $ticket_details->description,
                            'ticket_attachments' => $ticket_attachments,
                            'replies' => $replies,
                            'created_at' => $ticket_details->created_at,
                            'ticket_details' => $ticket_details->status,
                        ];

                    })
                    ->first();

        return response()->json([
            'ticket' => $ticket,
        ]);
    }

    public function sendReply(Request $request)
    {
        TicketReply::create([
            'ticket_id' => $request->ticket_id,
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        Ticket::where('id', $request->ticket_id)->update([
            'status' => 'in_progress',
            'last_replied_at' => now(),
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
        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_send_reply_success'),
            'type' => 'success'
        ]);

        // return redirect()->back()->with('toast', [
        //     'title' => trans('public.toast_send_reply_error'),
        //     'type' => 'error'
        // ]);
    }

    public function resolveTicket(Request $request)
    {
        Ticket::where('id', $request->ticket_id)->update([
            'status' => 'resolved',
            'closed_at' => now(),
        ]);

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_resolve_ticket_success'),
            'type' => 'success'
        ]);
    }

    public function markAsViewed(Request $request)
    {
        $user = Auth::user();
        $ticket_id = $request->input('ticket_id');

        TicketLog::updateOrCreate(
            [
                'user_id' => $user->id,
                'ticket_id' => $ticket_id,
            ],
            [
                'date_read' => now(),
            ]
        );

        return response()->json(['status' => 'ok']);
    }
}
