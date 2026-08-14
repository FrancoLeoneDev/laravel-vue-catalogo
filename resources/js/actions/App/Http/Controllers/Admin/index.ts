import DashboardController from './DashboardController'
import ProductController from './ProductController'
import CategoryController from './CategoryController'
import StockMovementController from './StockMovementController'
const Admin = {
    DashboardController: Object.assign(DashboardController, DashboardController),
ProductController: Object.assign(ProductController, ProductController),
CategoryController: Object.assign(CategoryController, CategoryController),
StockMovementController: Object.assign(StockMovementController, StockMovementController),
}

export default Admin