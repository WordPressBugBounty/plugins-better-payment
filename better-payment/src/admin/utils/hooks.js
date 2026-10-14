import { useState, useEffect, useCallback } from 'react';
import api, { apiUtils } from './api';

/**
 * Custom hook for API requests with loading and error states
 * @param {Function} apiCall - API function to call
 * @param {Array} dependencies - Dependencies for useEffect
 * @param {*} initialData - Initial data value
 * @returns {Object} { data, loading, error, refetch }
 */
export const useAPI = ( apiCall, dependencies = [], initialData = null ) => {
	const [ data, setData ] = useState( initialData );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	const fetchData = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );
			const result = await apiCall();
			setData( result );
		} catch ( err ) {
			setError( apiUtils.handleError( err ) );
		} finally {
			setLoading( false );
		}
	}, dependencies );

	useEffect( () => {
		fetchData();
	}, [ fetchData ] );

	const refetch = useCallback( () => {
		fetchData();
	}, [ fetchData ] );

	return { data, loading, error, refetch };
};

/**
 * Hook for dashboard statistics
 * @returns {Object} Dashboard stats with loading and error states
 */
export const useDashboardStats = () => {
	return useAPI( () => api.dashboard.getStats() );
};

/**
 * Hook for transactions with filtering
 * @param {Object} filters - Transaction filters
 * @returns {Object} Transactions data with loading and error states
 */
export const useTransactions = ( filters = {} ) => {
	return useAPI(
		() => api.transactions.getTransactions( filters ),
		[ JSON.stringify( filters ) ],
		{ transactions: [], total: 0, pages: 0 }
	);
};

/**
 * Hook for settings data
 * @returns {Object} Settings data with loading and error states
 */
export const useSettings = () => {
	return useAPI( () => api.settings.getSettings() );
};

/**
 * Hook for async actions with loading states
 * @returns {Object} { execute, loading, error, data }
 */
export const useAsyncAction = () => {
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ data, setData ] = useState( null );

	const execute = useCallback( async ( asyncFunction ) => {
		try {
			setLoading( true );
			setError( null );
			const result = await asyncFunction();
			setData( result );
			return result;
		} catch ( err ) {
			const formattedError = apiUtils.handleError( err );
			setError( formattedError );
			throw formattedError;
		} finally {
			setLoading( false );
		}
	}, [] );

	return { execute, loading, error, data };
};

/**
 * Hook for managing form state with validation
 * @param {Object} initialValues - Initial form values
 * @param {Function} validationSchema - Validation function
 * @returns {Object} Form state and handlers
 */
export const useForm = ( initialValues = {}, validationSchema = null ) => {
	const [ values, setValues ] = useState( initialValues );
	const [ errors, setErrors ] = useState( {} );
	const [ touched, setTouched ] = useState( {} );

	const setValue = useCallback(
		( name, value ) => {
			setValues( ( prev ) => ( {
				...prev,
				[ name ]: value,
			} ) );

			// Clear error when user starts typing
			if ( errors[ name ] ) {
				setErrors( ( prev ) => ( {
					...prev,
					[ name ]: null,
				} ) );
			}
		},
		[ errors ]
	);

	const setFieldTouched = useCallback( ( name ) => {
		setTouched( ( prev ) => ( {
			...prev,
			[ name ]: true,
		} ) );
	}, [] );

	const validate = useCallback( () => {
		if ( ! validationSchema ) return true;

		const validationErrors = validationSchema( values );
		setErrors( validationErrors );
		return Object.keys( validationErrors ).length === 0;
	}, [ values, validationSchema ] );

	const reset = useCallback( () => {
		setValues( initialValues );
		setErrors( {} );
		setTouched( {} );
	}, [ initialValues ] );

	return {
		values,
		errors,
		touched,
		setValue,
		setFieldTouched,
		validate,
		reset,
		isValid: Object.keys( errors ).length === 0,
	};
};

/**
 * Hook for managing pagination
 * @param {number} initialPage - Initial page number
 * @param {number} initialPerPage - Initial items per page
 * @returns {Object} Pagination state and handlers
 */
export const usePagination = ( initialPage = 1, initialPerPage = 10 ) => {
	const [ currentPage, setCurrentPage ] = useState( initialPage );
	const [ perPage, setPerPage ] = useState( initialPerPage );

	const goToPage = useCallback( ( page ) => {
		setCurrentPage( page );
	}, [] );

	const nextPage = useCallback( () => {
		setCurrentPage( ( prev ) => prev + 1 );
	}, [] );

	const prevPage = useCallback( () => {
		setCurrentPage( ( prev ) => Math.max( 1, prev - 1 ) );
	}, [] );

	const reset = useCallback( () => {
		setCurrentPage( initialPage );
		setPerPage( initialPerPage );
	}, [ initialPage, initialPerPage ] );

	return {
		currentPage,
		perPage,
		setPerPage,
		goToPage,
		nextPage,
		prevPage,
		reset,
	};
};

/**
 * Hook for managing filters
 * @param {Object} initialFilters - Initial filter values
 * @returns {Object} Filter state and handlers
 */
export const useFilters = ( initialFilters = {} ) => {
	const [ filters, setFilters ] = useState( initialFilters );

	const updateFilter = useCallback( ( key, value ) => {
		setFilters( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
	}, [] );

	const updateFilters = useCallback( ( newFilters ) => {
		setFilters( ( prev ) => ( {
			...prev,
			...newFilters,
		} ) );
	}, [] );

	const clearFilters = useCallback( () => {
		setFilters( initialFilters );
	}, [ initialFilters ] );

	const removeFilter = useCallback( ( key ) => {
		setFilters( ( prev ) => {
			const newFilters = { ...prev };
			delete newFilters[ key ];
			return newFilters;
		} );
	}, [] );

	return {
		filters,
		updateFilter,
		updateFilters,
		clearFilters,
		removeFilter,
	};
};

/**
 * Hook for managing local storage
 * @param {string} key - Storage key
 * @param {*} initialValue - Initial value
 * @returns {Array} [value, setValue]
 */
export const useLocalStorage = ( key, initialValue ) => {
	const [ storedValue, setStoredValue ] = useState( () => {
		try {
			const item = window.localStorage.getItem( key );
			return item ? JSON.parse( item ) : initialValue;
		} catch ( error ) {
			console.error(
				`Error reading localStorage key "${ key }":`,
				error
			);
			return initialValue;
		}
	} );

	const setValue = useCallback(
		( value ) => {
			try {
				const valueToStore =
					value instanceof Function ? value( storedValue ) : value;
				setStoredValue( valueToStore );
				window.localStorage.setItem(
					key,
					JSON.stringify( valueToStore )
				);
			} catch ( error ) {
				console.error(
					`Error setting localStorage key "${ key }":`,
					error
				);
			}
		},
		[ key, storedValue ]
	);

	return [ storedValue, setValue ];
};
