@forelse ($fields as $field)
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
        <td>{{ $field->tooltip }}</td>
        <td>
            <div class="d-inline-flex position-relative" role="group">
                <a href="{{ route('channel-lister-field.show', $field->id) }}"
                    class="btn btn-sm btn-outline-info">View</a>
                <a href="{{ route('channel-lister-field.edit', $field->id) }}"
                    class="btn btn-sm btn-outline-primary mx-1">Edit</a>
                <form class="d-inline" action="{{ route('channel-lister-field.destroy', $field->id) }}" method="POST">
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
@empty
    <tr>
        <td colspan="10" class="text-center py-5">
            <h5>No fields found</h5>
            <p class="text-muted">Try adjusting your search criteria.</p>
        </td>
    </tr>
@endforelse
