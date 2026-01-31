jQuery(function ($) {
    const tableBody = $('#lsls-languages-table tbody');
    const addButton = $('#lsls-add-row');

    function getNextIndex() {
        let maxIndex = -1;
        tableBody.find('tr').each(function () {
            const input = $(this).find('input[name*="[languages]"]').first();
            const match = input.attr('name').match(/languages\]\[(\d+)\]/);
            if (match && match[1]) {
                maxIndex = Math.max(maxIndex, parseInt(match[1], 10));
            }
        });
        return maxIndex + 1;
    }

    function createRow(index) {
        return `
            <tr>
                <td><input type="text" name="lsls_options[languages][${index}][code]" class="regular-text" /></td>
                <td><input type="text" name="lsls_options[languages][${index}][name]" class="regular-text" /></td>
                <td><input type="text" name="lsls_options[languages][${index}][flag]" class="regular-text" /></td>
                <td><input type="text" name="lsls_options[languages][${index}][url]" class="regular-text" /></td>
                <td><button type="button" class="button lsls-remove-row">${lslsAdmin.removeLabel}</button></td>
            </tr>
        `;
    }

    function reindexRows() {
        tableBody.find('tr').each(function (rowIndex) {
            $(this).find('input').each(function () {
                const name = $(this).attr('name');
                if (!name) return;
                const updated = name.replace(/languages\]\[(\d+)\]/, `languages][${rowIndex}]`);
                $(this).attr('name', updated);
            });
        });
    }

    addButton.on('click', function () {
        const index = getNextIndex();
        tableBody.append(createRow(index));
        reindexRows();
    });

    tableBody.on('click', '.lsls-remove-row', function () {
        const rows = tableBody.find('tr');
        if (rows.length <= 1) {
            rows.find('input').val('');
            return;
        }
        $(this).closest('tr').remove();
        reindexRows();
    });
});
