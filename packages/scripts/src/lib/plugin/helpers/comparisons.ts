// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const isEqual = (value: any, other: any): boolean => {
  // Check if both values are the same reference or strictly equal
  if (value === other) {
    return true;
  }

  // Handle null or undefined
  if (value == null || other == null) {
    return value === other;
  }

  // Handle NaN (NaN !== NaN, but they should be considered equal)
  if (Number.isNaN(value) && Number.isNaN(other)) {
    return true;
  }

  // Check if types are different
  if (typeof value !== typeof other) {
    return false;
  }

  // Handle arrays
  if (Array.isArray(value) && Array.isArray(other)) {
    if (value.length !== other.length) {
      return false;
    }

    // Recursively compare array elements
    return value.every((item, index) => isEqual(item, other[index]));
  }

  // Handle objects
  if (typeof value === 'object' && typeof other === 'object') {
    const valueKeys = Object.keys(value);
    const otherKeys = Object.keys(other);

    // Check if the objects have the same number of keys
    if (valueKeys.length !== otherKeys.length) {
      return false;
    }

    // Recursively compare object properties
    return valueKeys.every((key) => {
      return other.hasOwnProperty(key) && isEqual(value[key], other[key]);
    });
  }

  // Fallback for primitive types (number, string, boolean, etc.)
  return false;
};
