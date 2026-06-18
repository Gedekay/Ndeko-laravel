<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'all');

        $dateFrom = match ($period) {
            'today' => Carbon::today(),
            'week'  => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            default => null,
        };

        $usersQuery = User::query();
        $messagesQuery = Message::query();
        $conversationsQuery = Conversation::query();

        if ($dateFrom) {
            $usersQuery->where('created_at', '>=', $dateFrom);
            $messagesQuery->where('created_at', '>=', $dateFrom);
            $conversationsQuery->where('created_at', '>=', $dateFrom);
        }

        $today = Carbon::today();

        return response()->json([
            'success' => true,
            'period' => $period,

            'users' => [
                'total' => User::count(),
                'new' => $usersQuery->count(),
                'new_today' => User::whereDate('created_at', $today)->count(),
                'online' => User::where('is_online', true)->count(),
                'blocked' => User::where('is_blocked', true)->count(),
                'active_today' => User::whereDate('last_seen', $today)->count(),
            ],

           
            'messages' => [
                'total' => Message::count(),
                'new' => $messagesQuery->count(),
                'today' => Message::whereDate('created_at', $today)->count(),
                'last_24h' => Message::where('created_at', '>=', now()->subDay())->count(),
            ],

         
            'conversations' => [
                'total' => Conversation::count(),
                'private' => Conversation::where('type', 'private')->count(),
                'group' => Conversation::where('type', 'group')->count(),
                'new' => $conversationsQuery->count(),
                'active_today' => Conversation::whereDate('updated_at', $today)->count(),
            ],

            'groups' => [
                'total' => Group::count(),
            ],

            
            'activity' => [
                'users_online' => User::where('is_online', true)->count(),

                'messages_today' => Message::whereDate('created_at', $today)->count(),

                'active_conversations' => Conversation::whereDate('updated_at', '>=', $today)->count(),

                /**
                 * 📈 Messages par heure (graph)
                 */
                'messages_per_hour' => Message::selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
                    ->whereDate('created_at', $today)
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->get(),

             
                'users_per_day' => User::selectRaw('DATE(created_at) as date, COUNT(*) as total')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
            ],

            
            'recent' => [
                'users' => User::latest()
                    ->take(5)
                    ->get(['id', 'fullname', 'phone', 'created_at']),

                'messages' => Message::latest()
                    ->take(5)
                    ->with('sender:id,fullname')
                    ->get(),

                'conversations' => Conversation::latest()
                    ->take(5)
                    ->with('members:id,fullname')
                    ->get(),
            ],
        ]);
    }
}