import { createContext, useContext } from 'react';

export const SearchContext = createContext<{
  open: boolean;
  setOpen: (open: boolean) => void;
}>({ open: false, setOpen: () => {} });

export const useSearch = () => useContext(SearchContext);