
export function useExport() {
    const exportToCsv = (filename, rows, columns = null) => {
        if (!rows || !rows.length) {
            alert("Tidak ada data untuk diekspor.");
            return;
        }

        // Determine columns from first row if not provided
        const headers = columns || Object.keys(rows[0]);

        // CSV Header
        const csvContent = [
            headers.join(","), // Header Row
            ...rows.map(row => headers.map(fieldName => {
                let cell = row[fieldName] === null || row[fieldName] === undefined ? "" : row[fieldName];

                // Escape quotes and wrap in quotes if contains comma
                cell = cell.toString().replace(/"/g, '""');
                if (cell.search(/("|,|\n)/g) >= 0) {
                    cell = `"${cell}"`;
                }
                return cell;
            }).join(","))
        ].join("\n");

        // Download Trigger
        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", `${filename}.csv`);
            link.style.visibility = "hidden";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };

    return { exportToCsv };
}
