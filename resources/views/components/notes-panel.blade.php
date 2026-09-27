@props(['notable', 'storeRoute'])

@if (auth()->user()->hasPermission('notes.view'))
    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Notes</h3>

        <div class="space-y-3 mb-4 max-h-72 overflow-y-auto">
            @forelse ($notable->notes as $note)
                <div class="text-sm bg-gray-50 rounded-md p-3">
                    <p class="text-gray-800">{{ $note->body }}</p>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-xs text-gray-500">
                            {{ $note->author->name ?? 'Unknown' }} &middot; {{ $note->created_at->diffForHumans() }}
                        </span>
                        @if ($note->user_id === auth()->id() || auth()->user()->hasPermission('institution.setup'))
                            <x-delete-button
                                :action="route('ict-admin.notes.destroy', $note)"
                                label="Remove"
                                confirm-title="Remove this note?"
                                confirm-message="This cannot be undone."
                            />
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No notes yet.</p>
            @endforelse
        </div>

        @if (auth()->user()->hasPermission('notes.create'))
            <form method="POST" action="{{ $storeRoute }}" class="space-y-2">
                @csrf
                <textarea name="body" rows="2" placeholder="Add a note..."
                          class="w-full rounded-md border-gray-300 shadow-sm text-sm" required></textarea>
                <div class="flex justify-end">
                    <x-btn-primary type="submit" loading-text="Adding...">Add Note</x-btn-primary>
                </div>
            </form>
        @endif
    </div>
@endif
