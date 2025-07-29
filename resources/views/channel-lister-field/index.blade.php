<x-channel-lister::layout>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1>Channel Lister Fields</h1>
                    <a href="{{ route('channel-lister-field.create') }}" class="btn btn-primary">
                        Create New Field
                    </a>
                </div>

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        @if ($fields->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Field Name</th>
                                            <th>Display Name</th>
                                            <th>Marketplace</th>
                                            <th>Input Type</th>
                                            <th>Required</th>
                                            <th>Grouping</th>
                                            <th>Type</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($fields as $field)
                                            <tr>
                                                <td>{{ $field->ordering }}</td>
                                                <td>{{ $field->field_name }}</td>
                                                <td>{{ $field->display_name }}</td>
                                                <td>
                                                    <span class="badge badge-secondary">{{ $field->marketplace }}</span>
                                                </td>
                                                <td>{{ $field->input_type->value }}</td>
                                                <td>
                                                    @if ($field->required)
                                                        <span class="badge badge-danger">Required</span>
                                                    @else
                                                        <span class="badge badge-secondary">Optional</span>
                                                    @endif
                                                </td>
                                                <td>{{ $field->grouping }}</td>
                                                <td>{{ $field->type->value }}</td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('channel-lister-field.show', $field->id) }}"
                                                            class="btn btn-sm btn-outline-info">View</a>
                                                        <a href="{{ route('channel-lister-field.edit', $field->id) }}"
                                                            class="btn btn-sm btn-outline-primary">Edit</a>
                                                        <form
                                                            action="{{ route('channel-lister-field.destroy', $field->id) }}"
                                                            method="POST" style="display: inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Are you sure you want to delete this field?')">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <h4>No Channel Lister Fields Found</h4>
                                <p class="text-muted">Get started by creating your first field.</p>
                                <a href="{{ route('channel-lister-field.create') }}" class="btn btn-primary">
                                    Create New Field
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-channel-lister::layout>
