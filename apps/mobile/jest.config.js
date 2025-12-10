module.exports = {
  testEnvironment: 'node',
  transform: {
    '^.+\\.(ts|tsx)$': 'babel-jest',
  },
  transformIgnorePatterns: [],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
  },
  testPathIgnorePatterns: ['/node_modules/', '/android/', '/ios/'],
  moduleFileExtensions: ['ts', 'tsx', 'js', 'jsx', 'json'],
};
