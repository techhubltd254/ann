import React from 'react';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { NavigationContainer } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { AuthProvider } from './src/lib/auth';
import { CartProvider } from './src/lib/cart';

// Screens (stubs — to be built out)
import HomeScreen from './src/screens/HomeScreen';
import SearchScreen from './src/screens/SearchScreen';
import CartScreen from './src/screens/CartScreen';
import ProfileScreen from './src/screens/ProfileScreen';
import CountyListScreen from './src/screens/CountyListScreen';
import SectorScreen from './src/screens/SectorScreen';
import ProductScreen from './src/screens/ProductScreen';

const Tab = createBottomTabNavigator();
const Stack = createNativeStackNavigator();

function MainTabs() {
  return (
    <Tab.Navigator screenOptions={{ headerShown: false }}>
      <Tab.Screen name="Home" component={HomeScreen} />
      <Tab.Screen name="Search" component={SearchScreen} />
      <Tab.Screen name="Cart" component={CartScreen} />
      <Tab.Screen name="Profile" component={ProfileScreen} />
    </Tab.Navigator>
  );
}

export default function App() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <CartProvider>
          <NavigationContainer>
            <Stack.Navigator>
              <Stack.Screen name="Main" component={MainTabs} options={{ headerShown: false }} />
              <Stack.Screen name="Counties" component={CountyListScreen} options={{ title: 'County' }} />
              <Stack.Screen name="Sector" component={SectorScreen} options={{ title: 'Sector' }} />
              <Stack.Screen name="Product" component={ProductScreen} options={{ title: 'Product' }} />
            </Stack.Navigator>
          </NavigationContainer>
        </CartProvider>
        <StatusBar style="auto" />
      </AuthProvider>
    </SafeAreaProvider>
  );
}
