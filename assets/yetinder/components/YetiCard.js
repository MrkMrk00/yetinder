import React, { useEffect, useState } from 'react'

const YetiCard = (props) => {
    const [yeti, setYeti] = useState({})
    const [sex, setSex] = useState('')

    useEffect(() => {
        setYeti(props.yeti)
    },[props.yeti])

    useEffect(() => {
        if (!yeti) return
        if (yeti.sex === 'male') setSex('M')
        else if (yeti.sex === 'female') setSex('Ž')
    }, [yeti])

    return (
        <div className={'col-md-6 col-xl-4'}>
            <div className="card m-3">
                <img className="card-img-top" src="/icons/yetis/00.jpg" alt="yeti"
                     style={{height: '170px', objectFit: 'none', objectPosition: '50% 32%'}}
                />
                <div className="card-body">
                    <h3 className="d-inline-block card-title">{ yeti.name }</h3>
                    <small className="d-inline-block text-muted">({ sex })</small>
                    <ul className="list-group list-group-flush">
                        <li className="list-group-item" style={{display: 'flex'}}>
                            <span className="w-50 text-left px-2"
                                  >Výška:</span>
                            { yeti.height } cm
                        </li>
                        <li className="list-group-item" style={{display: 'flex'}}>
                            <span className="w-50 text-left px-2"
                                  style={{display: 'inline-block'}}>Váha:</span>
                            { yeti.weight } kg
                        </li>
                        <li className="list-group-item" style={{display: 'flex'}}>
                            <span className="w-50 text-left px-2" style={{display: 'inline-block'}}>Věk:</span>
                            { yeti.age }
                        </li>
                        <li className="list-group-item" style={{display: 'flex'}}>
                            <span className="w-50 text-left px-2" style={{display: 'inline-block'}}>Barva:</span>
                            { yeti.color ? yeti.color.color : null}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    )
}

export default YetiCard