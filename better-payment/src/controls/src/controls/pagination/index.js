
import { useState, useEffect } from "@wordpress/element";

function Items({ currentItems }) {
    return (
        <>
            {currentItems && currentItems.map((item, index) => (
                <div key={index}>
                    <h3>Item #{item}</h3>
                </div>
            ))}
        </>
    );
}

export default function Pagination(props) {
    const {
        totalPages = 1,
        pageNumber = 1,
        prefix = 'eb',
        visible = 3,
        setPageNumber,
    } = props;

    //Create Array from "totalPages"
    const totalPaginationFunc = () => {
        let numbers = [];
        if (totalPages && typeof totalPages === 'number') {
            for (let i = 1; i <= totalPages; i++) {
                numbers.push(i)
            }
        }
        return numbers;
    }
    const items = totalPaginationFunc();

    const isItemVisible = (item, visible) => {
        const visibleSide = Math.floor(visible / 2)

        if (item === pageNumber || item === 1 || item === totalPages) {
            return true;
        }

        if (item === (pageNumber - visibleSide) || item === (pageNumber + visibleSide)) {
            return true
        }

        return false;
    }

    const itemsPerPage = 1;

    // We start with an empty list of items.
    const [currentItems, setCurrentItems] = useState(null);
    const [pageCount, setPageCount] = useState(0);
    // Here we use item offsets; we could also use page offsets
    // following the API or data you're working with.
    const [itemOffset, setItemOffset] = useState(0);

    useEffect(() => {
        // Fetch items from another resources.
        const endOffset = itemOffset + itemsPerPage;
        // console.log(`Loading items from ${itemOffset} to ${endOffset}`);
        setCurrentItems(items.slice(itemOffset, endOffset));
        setPageCount(Math.ceil(items.length / itemsPerPage));
    }, [itemOffset, itemsPerPage]);

    // Invoke when user click to request another page.
    const handlePageClick = (event) => {
        const newOffset = (event.selected * itemsPerPage) % items.length;
        // console.log(`User requested page number ${event.selected}, which is offset ${newOffset}`);
        setItemOffset(newOffset);
    };

    return (
        <>
            {/* <Items currentItems={currentItems} />
            <ReactPaginate
                breakLabel="..."
                nextLabel="next >"
                onPageChange={handlePageClick}
                pageRangeDisplayed={3}
                pageCount={pageCount}
                previousLabel="< previous"
                renderOnZeroPageCount={null}
            /> */}
        </>
    );
}
