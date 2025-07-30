<x-channel-lister::layout>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1>Channel Lister Field Details</h1>
                    <div>
                        <a href="{{ route('channel-lister-field.edit', $field->id) }}" class="btn btn-primary">
                            Edit Field
                        </a>
                        <a href="{{ route('channel-lister-field.index') }}" class="btn btn-secondary">
                            Back to List
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Field Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">ID</th>
                                        <td>{{ $field->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Field Name</th>
                                        <td><code>{{ $field->field_name }}</code></td>
                                    </tr>
                                    <tr>
                                        <th>Display Name</th>
                                        <td>{{ $field->display_name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Marketplace</th>
                                        <td>
                                            <span class="badge badge-secondary">{{ $field->marketplace }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Ordering</th>
                                        <td>{{ $field->ordering }}</td>
                                    </tr>
                                    <tr>
                                        <th>Required</th>
                                        <td>
                                            @if ($field->required)
                                                <span class="badge badge-danger">Required</span>
                                            @else
                                                <span class="badge badge-secondary">Optional</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Field Configuration</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">Input Type</th>
                                        <td>
                                            <span class="badge badge-info">{{ $field->input_type->value }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Type</th>
                                        <td>
                                            <span class="badge badge-primary">{{ $field->type->value }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Grouping</th>
                                        <td>{{ $field->grouping ?: 'None' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tool Tip</th>
                                        <td>{{ $field->tooltip ?: 'None' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Example</th>
                                        <td>{{ $field->example ?: 'None' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td>{{ $field->created_at->format('M j, Y g:i A') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated</th>
                                        <td>{{ $field->updated_at->format('M j, Y g:i A') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if ($field->default_value || $field->field_value || $field->extra_json)
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5>Additional Information</h5>
                                    <div class="row">
                                        @if ($field->default_value)
                                            <div class="col-md-4">
                                                <strong>Default Value:</strong>
                                                <pre class="mt-2 p-2 bg-light">{{ $field->default_value }}</pre>
                                            </div>
                                        @endif
                                        @if ($field->field_value)
                                            <div class="col-md-4">
                                                <strong>Field Value:</strong>
                                                <pre class="mt-2 p-2 bg-light">{{ $field->field_value }}</pre>
                                            </div>
                                        @endif
                                        @if ($field->extra_json)
                                            <div class="col-md-4">
                                                <strong>Extra JSON:</strong>
                                                <pre class="mt-2 p-2 bg-light">{{ json_encode(json_decode($field->extra_json), JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('channel-lister-field.index') }}" class="btn btn-secondary">
                                        ← Back to List
                                    </a>
                                    <div>
                                        <a href="{{ route('channel-lister-field.edit', $field->id) }}"
                                            class="btn btn-primary">
                                            Edit Field
                                        </a>
                                        <form action="{{ route('channel-lister-field.destroy', $field->id) }}"
                                            method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this field? This action cannot be undone.')">
                                                Delete Field
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-channel-lister::layout>
