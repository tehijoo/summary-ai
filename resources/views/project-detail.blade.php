@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const generateBtn = document.getElementById('generate-flashcards-btn');
    if (!generateBtn) return;

    const btnIcon = document.getElementById('btn-icon');
    const btnText = document.getElementById('btn-text');

    // These are now used for the *final save*, not the generation step
    const docTitle = @json('AI Flashcards for: ' . Str::limit($conversation->document->original_filename, 50));
    const docDesc = @json('Automatically generated from a document.');

    generateBtn.addEventListener('click', async function() {
        this.disabled = true;
        btnIcon.textContent = 'hourglass_top';
        btnIcon.classList.add('animate-spin');
        btnText.textContent = 'AI is Generating...';

        try {
            // --- CHANGE START: Call our own secure server route ---
            const generationResponse = await fetch('{{ route("ai.generate-flashcards", $conversation->document->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (!generationResponse.ok) {
                const errorData = await generationResponse.json();
                throw new Error(errorData.error || 'Failed to get response from the server.');
            }
            
            const flashcardsData = await generationResponse.json();
            // --- CHANGE END ---


            // This part stays the same: save the flashcards we received from our server
            const saveResponse = await fetch('{{ route("flashcards.ai-save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    title: docTitle,
                    description: docDesc,
                    flashcardsData: flashcardsData // Use the data from our server
                })
            });

            if (!saveResponse.ok) throw new Error('Failed to save flashcards to the database.');

            const saveData = await saveResponse.json();
            window.location.href = saveData.redirect_url; // Redirect on success

        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while generating flashcards. ' + error.message);
            this.disabled = false;
            btnIcon.textContent = 'auto_awesome';
            btnIcon.classList.remove('animate-spin');
            btnText.textContent = 'Generate Flashcards';
        }
    });
});
</script>
@endsection