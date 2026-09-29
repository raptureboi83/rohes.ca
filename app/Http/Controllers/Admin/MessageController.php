<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InvalidatesContentCache;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    use InvalidatesContentCache;

    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'inbox');

        // Only the three known filters are honoured, so a hand-typed query
        // string cannot reach the query builder in an unexpected state.
        $filter = in_array($filter, ['inbox', 'unread', 'archived'], true) ? $filter : 'inbox';

        $query = Message::query()->where('is_archived', $filter === 'archived');

        if ($filter === 'unread') {
            $query->unread();
        }

        return view('admin.messages.index', [
            'messages' => $query->latest()->paginate(30)->withQueryString(),
            'filter' => $filter,
            'inboxCount' => Message::query()->inbox()->count(),
            'unreadCount' => Message::query()->inbox()->unread()->count(),
            'archivedCount' => Message::query()->where('is_archived', true)->count(),
        ]);
    }

    public function show(Message $message): View
    {
        // Opening a message marks it read, which is what an editor expects.
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
            $this->forgetContentCache();
        }

        return view('admin.messages.show', ['message' => $message]);
    }

    public function toggleRead(Message $message): RedirectResponse
    {
        $message->update(['is_read' => ! $message->is_read]);

        $this->forgetContentCache();

        return back()->with('success', $message->is_read ? 'Marked as read.' : 'Marked as unread.');
    }

    public function destroy(Message $message): RedirectResponse
    {
        $message->delete();

        $this->forgetContentCache();

        return back()->with('success', 'Message deleted.');
    }
}
