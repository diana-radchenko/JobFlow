export const stringForHuman = (myString: string): string => {
    return myString
        .split('_')
        .map((word: string) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};
