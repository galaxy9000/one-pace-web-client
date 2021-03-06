import React from 'react'
import ReactDOM from 'react-dom'
import { BrowserRouter as Router, Route, Switch } from 'react-router-dom'
import 'babel-polyfill'
import Watch from './watch'
import About from './about'
import Layout from './layout'
import { Overview } from './Overview'
import '../index.scss'
import '../index.html'
import '../images/favicon.ico'
import 'typeface-roboto'
require.context('../images', false, /arc_\d+\.png$/)

ReactDOM.render((
  <Router>
    <Layout>
      <Switch>
        <Route exact path='/' component={Watch} />
        <Route path={['/about', '/about.html']} component={About} />
        <Route path={['/overview', '/overview.html']} component={Overview} />
      </Switch>
    </Layout>
  </Router>
), document.getElementById('reactentry'))
